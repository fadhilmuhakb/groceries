<?php

namespace App\Http\Controllers;

use App\Models\tb_stores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class ProductStockController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $stores       = $isSuperadmin
            ? tb_stores::select('id', 'store_name')->orderBy('store_name')->get()
            : collect();

        $selectedStoreId = $isSuperadmin ? $request->query('store') : ($user?->store_id);
        $currentStore    = $selectedStoreId
            ? tb_stores::where('id', $selectedStoreId)->value('store_name')
            : null;

        return view('pages.admin.master.stock-overview', [
            'isSuperadmin'    => $isSuperadmin,
            'stores'          => $stores,
            'selectedStoreId' => $selectedStoreId,
            'currentStore'    => $currentStore,
        ]);
    }

    public function data(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $storeId      = $isSuperadmin ? $request->get('store') : ($user?->store_id);

        if (!$storeId) {
            return DataTables::of(collect())->toJson();
        }

        $incomingSub = DB::table('tb_incoming_goods as ig')
            ->when(Schema::hasColumn('tb_incoming_goods', 'store_id'),
                fn($q) => $q->where('ig.store_id', $storeId),
                fn($q) => $q->join('tb_purchases as pur', 'ig.purchase_id', '=', 'pur.id')
                             ->where('pur.store_id', $storeId)
            )
            ->select('ig.product_id', DB::raw('SUM(ig.stock) AS total_in'))
            ->groupBy('ig.product_id');

        $base = DB::table('tb_products as p')
            ->leftJoinSub($incomingSub, 'incoming', fn($join) => $join->on('incoming.product_id', '=', 'p.id'))
            ->select(
                'p.id',
                'p.product_code',
                'p.product_name',
                DB::raw('COALESCE(incoming.total_in, 0) as stock_system')
            )
            ->where(DB::raw('COALESCE(incoming.total_in, 0)'), '>', 0)
            ->orderBy('p.product_name');

        return DataTables::of($base)
            ->addIndexColumn()
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $like = '%' . $search . '%';
                        $q->where('p.product_name', 'like', $like)
                          ->orWhere('p.product_code', 'like', $like);
                    });
                }
            })
            ->toJson();
    }
}
