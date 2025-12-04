<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderStockController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $storeId      = $isSuperadmin ? (int)$request->get('store') : (int)($user?->store_id);

        $stores = $isSuperadmin
            ? DB::table('tb_stores')->select('id', 'store_name')->orderBy('store_name')->get()
            : collect();

        if ($isSuperadmin && !$storeId) {
            return view('pages.admin.order-stock.index', [
                'stores'       => $stores,
                'selected'     => null,
                'items'        => collect(),
                'isSuperadmin' => $isSuperadmin,
                'currentStore' => null,
            ]);
        }

        if (!$storeId) {
            return redirect()->back()->with('warning', 'Pilih toko terlebih dahulu.');
        }

        $items = $this->lowStockQuery($storeId)->get();
        $currentStore = DB::table('tb_stores')->where('id', $storeId)->value('store_name');

        return view('pages.admin.order-stock.index', [
            'stores'       => $stores,
            'selected'     => $storeId,
            'items'        => $items,
            'isSuperadmin' => $isSuperadmin,
            'currentStore' => $currentStore,
        ]);
    }

    public function restock(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $storeId      = $isSuperadmin ? (int)$request->input('store_id') : (int)($user?->store_id);
        if (!$storeId) return back()->with('error', 'Pilih toko terlebih dahulu.');

        $items = array_filter($request->input('items', []), fn ($v) => $v !== null && $v !== '');
        if (empty($items)) return back()->with('warning', 'Tidak ada produk yang dipilih.');

        $products = $this->lowStockQuery($storeId)
            ->whereIn('p.id', $items)
            ->whereNotNull('sp.max_stock')
            ->get();

        $restockRows   = [];
        $totalPurchase = 0;
        $now           = now();

        foreach ($products as $row) {
            $needed = max(0, (int)$row->max_stock - (int)$row->stock_system);
            if ($needed <= 0) continue;

            $restockRows[] = [
                'product_id' => (int)$row->id,
                'qty'        => $needed,
                'price'      => (float)$row->purchase_price,
            ];
            $totalPurchase += $needed * (float)$row->purchase_price;
        }

        if (empty($restockRows)) {
            return back()->with('warning', 'Semua stok sudah maksimal.');
        }

        DB::beginTransaction();
        try {
            $purchaseId = DB::table('tb_purchases')->insertGetId([
                'supplier_id' => null,
                'store_id'    => $storeId,
                'total_price' => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $rows = [];
            foreach ($restockRows as $row) {
                $payload = [
                    'purchase_id'  => $purchaseId,
                    'product_id'   => $row['product_id'],
                    'stock'        => $row['qty'],
                    'description'  => 'Restock hingga stok maksimum',
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
                if (Schema::hasColumn('tb_incoming_goods', 'store_id')) {
                    $payload['store_id'] = $storeId;
                }
                if (Schema::hasColumn('tb_incoming_goods', 'is_pending_stock')) {
                    $payload['is_pending_stock'] = false;
                }
                $rows[] = $payload;
            }

            DB::table('tb_incoming_goods')->insert($rows);
            DB::table('tb_purchases')->where('id', $purchaseId)->update([
                'total_price' => $totalPurchase,
                'updated_at'  => $now,
            ]);

            DB::commit();
            return back()->with('success', 'Stok berhasil diatur ke nilai maksimum.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    private function lowStockQuery(int $storeId)
    {
        $incomingSub = DB::table('tb_incoming_goods as ig')
            ->when(
                Schema::hasColumn('tb_incoming_goods', 'store_id'),
                fn ($q) => $q->where('ig.store_id', $storeId)
                             ->when(Schema::hasColumn('tb_incoming_goods', 'is_pending_stock'),
                                 fn ($q2) => $q2->where('ig.is_pending_stock', false)),
                fn ($q) => $q->join('tb_purchases as pur', 'ig.purchase_id', '=', 'pur.id')
                             ->where('pur.store_id', $storeId)
                             ->when(Schema::hasColumn('tb_incoming_goods', 'is_pending_stock'),
                                 fn ($q2) => $q2->where('ig.is_pending_stock', false))
            )
            ->select('ig.product_id', DB::raw('SUM(ig.stock) AS total_in'))
            ->groupBy('ig.product_id');

        $outgoingSub = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as sl', 'og.sell_id', '=', 'sl.id')
            ->where('sl.store_id', $storeId)
            ->when(Schema::hasColumn('tb_outgoing_goods', 'is_pending_stock'),
                fn ($q) => $q->where('og.is_pending_stock', false))
            ->select('og.product_id', DB::raw('SUM(og.quantity_out) AS total_out'))
            ->groupBy('og.product_id');

        return DB::table('tb_products as p')
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
                DB::raw('(COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)) as stock_system'),
                DB::raw('COALESCE(sp.purchase_price, p.purchase_price) as purchase_price')
            )
            ->whereNotNull('sp.min_stock')
            ->whereRaw('COALESCE(sp.min_stock,0) > 0')
            ->whereRaw('(COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)) <= COALESCE(sp.min_stock, 0)')
            ->orderBy('p.product_name');
    }
}
