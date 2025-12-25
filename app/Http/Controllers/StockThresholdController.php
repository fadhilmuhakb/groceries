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
            ->when(
                Schema::hasColumn('tb_incoming_goods', 'store_id'),
                fn ($q) => $q->where(function ($qq) use ($storeId) {
                    $qq->where('ig.store_id', $storeId)
                       ->orWhereExists(function ($ex) use ($storeId) {
                           $ex->select(DB::raw(1))
                              ->from('tb_purchases as pur')
                              ->whereColumn('pur.id', 'ig.purchase_id')
                              ->where('pur.store_id', $storeId);
                       });
                })->when(Schema::hasColumn('tb_incoming_goods', 'is_pending_stock'),
                    fn ($q2) => $q2->where(function ($w) {
                        $w->whereNull('ig.is_pending_stock')
                          ->orWhere('ig.is_pending_stock', false);
                    })),
                fn ($q) => $q->join('tb_purchases as pur', 'ig.purchase_id', '=', 'pur.id')
                             ->where('pur.store_id', $storeId)
                             ->when(Schema::hasColumn('tb_incoming_goods', 'is_pending_stock'),
                                 fn ($q2) => $q2->where(function ($w) {
                                     $w->whereNull('ig.is_pending_stock')
                                       ->orWhere('ig.is_pending_stock', false);
                                 }))
            )
            ->select('ig.product_id', DB::raw('SUM(ig.stock) AS total_in'))
            ->groupBy('ig.product_id');

        $outgoingSub = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as sl', 'og.sell_id', '=', 'sl.id')
            ->where('sl.store_id', $storeId)
            ->when(Schema::hasColumn('tb_outgoing_goods', 'is_pending_stock'),
                function ($q) {
                    $q->where(function ($qq) {
                        $qq->whereNull('og.is_pending_stock')
                           ->orWhere('og.is_pending_stock', false);
                    });
                })
            ->select('og.product_id', DB::raw('SUM(og.quantity_out) AS total_out'))
            ->groupBy('og.product_id');

        $rows = DB::table('tb_products as p')
            ->leftJoin('tb_product_store_thresholds as st', function ($join) use ($storeId) {
                $join->on('st.product_id', '=', 'p.id')
                     ->where('st.store_id', '=', $storeId);
            })
            ->leftJoinSub($incomingSub, 'incoming', fn ($join) => $join->on('incoming.product_id', '=', 'p.id'))
            ->leftJoinSub($outgoingSub, 'outgoing', fn ($join) => $join->on('outgoing.product_id', '=', 'p.id'))
            ->select(
                'p.id',
                'p.product_code',
                'p.product_name',
                'st.min_stock',
                'st.max_stock',
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
                $minStock = ($min === '' || $min === null) ? null : (int)$min;
                $maxStock = ($max === '' || $max === null) ? null : (int)$max;
                if ($minStock !== null && $maxStock !== null && $maxStock < $minStock) {
                $maxStock = $minStock;
            }

            $existing = DB::table('tb_product_store_thresholds')
                ->where('product_id', $pid)
                ->where('store_id', $storeId)
                ->first();

            if ($existing) {
                DB::table('tb_product_store_thresholds')
                    ->where('id', $existing->id)
                    ->update([
                        'min_stock'      => $minStock,
                        'max_stock'      => $maxStock,
                        'updated_at'     => now(),
                    ]);
            } else {
                DB::table('tb_product_store_thresholds')->insert([
                    'product_id'      => $pid,
                    'store_id'        => $storeId,
                    'min_stock'       => $minStock,
                    'max_stock'       => $maxStock,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
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
