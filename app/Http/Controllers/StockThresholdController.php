<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockThresholdController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $storeId      = $isSuperadmin ? (int)$request->get('store') : (int)($user?->store_id);
        $search       = trim((string)$request->get('q', ''));

        $stores = DB::table('tb_stores')->select('id', 'store_name')->orderBy('store_name')->get();

        if (!$storeId && $isSuperadmin) {
            return view('pages.admin.threshold.index', [
                'stores' => $stores,
                'storeId'=> null,
                'rows'   => collect(),
                'search' => $search,
            ]);
        }

        if (!$storeId) {
            return redirect()->back()->with('warning', 'Pilih toko terlebih dahulu.');
        }

        $incomingSub = DB::table('tb_incoming_goods as ig')
            ->join('tb_purchases as pur', 'ig.purchase_id', '=', 'pur.id')
            ->where('pur.store_id', $storeId)
            ->when(Schema::hasColumn('tb_incoming_goods', 'is_pending_stock'),
                fn ($q) => $q->where('ig.is_pending_stock', false))
            ->select('ig.product_id', DB::raw('SUM(ig.stock) AS total_in'))
            ->groupBy('ig.product_id');

        $outgoingSub = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as sl', 'og.sell_id', '=', 'sl.id')
            ->where('sl.store_id', $storeId)
            ->when(Schema::hasColumn('tb_outgoing_goods', 'is_pending_stock'),
                fn ($q) => $q->where('og.is_pending_stock', false))
            ->select('og.product_id', DB::raw('SUM(og.quantity_out) AS total_out'))
            ->groupBy('og.product_id');

        $rows = DB::table('tb_products as p')
            ->leftJoin('tb_product_store_prices as sp', function ($join) use ($storeId) {
                $join->on('sp.product_id', '=', 'p.id')
                     ->where('sp.store_id', '=', $storeId);
            })
            ->leftJoinSub($incomingSub, 'incoming', fn ($join) => $join->on('incoming.product_id', '=', 'p.id'))
            ->leftJoinSub($outgoingSub, 'outgoing', fn ($join) => $join->on('outgoing.product_id', '=', 'p.id'))
            ->select(
                'p.id',
                'p.product_code',
                'p.product_name',
                'sp.min_stock',
                'sp.max_stock',
                DB::raw('(COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)) as stock_system')
            )
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('p.product_name', 'like', $like)
                       ->orWhere('p.product_code', 'like', $like);
                });
            })
            ->orderBy('p.product_name')
            ->get();

        return view('pages.admin.threshold.index', [
            'stores' => $stores,
            'storeId'=> $storeId,
            'rows'   => $rows,
            'search' => $search,
        ]);
    }

    public function save(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $storeId      = $isSuperadmin ? (int)$request->input('store_id') : (int)($user?->store_id);

        if (!$storeId) return back()->with('error', 'Store wajib dipilih.');

        $items = $request->input('items', []);
        DB::beginTransaction();
        try {
            foreach ($items as $pid => $row) {
                $pid = (int)$pid;
                if ($pid <= 0) continue;
                $min = $row['min_stock'] ?? null;
                $max = $row['max_stock'] ?? null;
                $payload = [
                    'min_stock' => $min === '' ? null : (int)$min,
                    'max_stock' => $max === '' ? null : (int)$max,
                ];

                $existing = DB::table('tb_product_store_prices')
                    ->where('product_id', $pid)
                    ->where('store_id', $storeId)
                    ->first();

                if ($existing) {
                    DB::table('tb_product_store_prices')
                        ->where('id', $existing->id)
                        ->update(array_merge($payload, [
                            'updated_at' => now(),
                        ]));
                } else {
                    DB::table('tb_product_store_prices')->insert(array_merge($payload, [
                        'product_id' => $pid,
                        'store_id'   => $storeId,
                        'purchase_price' => 0,
                        'selling_price'  => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
            DB::commit();
            return back()->with('success', 'Batas stok berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}
