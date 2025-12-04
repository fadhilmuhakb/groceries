<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockThresholdController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $storeId      = $isSuperadmin ? (int)$request->get('store') : (int)($user?->store_id);

        $stores = DB::table('tb_stores')->select('id', 'store_name')->orderBy('store_name')->get();

        if (!$storeId && $isSuperadmin) {
            return view('pages.admin.threshold.index', [
                'stores' => $stores,
                'storeId'=> null,
                'rows'   => collect(),
            ]);
        }

        if (!$storeId) {
            return redirect()->back()->with('warning', 'Pilih toko terlebih dahulu.');
        }

        $rows = DB::table('tb_products as p')
            ->leftJoin('tb_product_store_prices as sp', function ($join) use ($storeId) {
                $join->on('sp.product_id', '=', 'p.id')
                     ->where('sp.store_id', '=', $storeId);
            })
            ->select(
                'p.id',
                'p.product_code',
                'p.product_name',
                'sp.min_stock',
                'sp.max_stock'
            )
            ->orderBy('p.product_name')
            ->get();

        return view('pages.admin.threshold.index', [
            'stores' => $stores,
            'storeId'=> $storeId,
            'rows'   => $rows,
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
