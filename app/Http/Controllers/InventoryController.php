<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\tb_stores;
use App\Models\tb_products;
use App\Models\tb_stock_opnames;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $getRoles = $user->roles;
        $storeId  = $getRoles === 'superadmin' ? $request->get('store_id') : $user->store_id;

        if ($getRoles === 'superadmin' && !$storeId) {
            $query  = collect();
            $stores = tb_stores::all();
            return view('pages.admin.inventory.index', compact('query', 'stores', 'storeId'));
        }

        $incomingSub = DB::table('tb_incoming_goods as ig')
            ->join('tb_purchases as p', 'ig.purchase_id', '=', 'p.id')
            ->where('p.store_id', $storeId)
            ->select('ig.product_id', DB::raw('SUM(ig.stock) AS total_in'))
            ->groupBy('ig.product_id');

        $outgoingSub = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as sl', 'og.sell_id', '=', 'sl.id')
            ->where('sl.store_id', $storeId)
            ->select('og.product_id', DB::raw('SUM(og.quantity_out) AS total_out'))
            ->groupBy('og.product_id');

        $query = DB::table('tb_products as pr')
            ->join('tb_stores as s', function ($join) use ($storeId) {
                $join->on('s.id', '=', DB::raw((int)$storeId));
            })
            ->leftJoin('tb_product_store_prices as sp', function ($join) use ($storeId) {
                $join->on('sp.product_id', '=', 'pr.id')
                     ->where('sp.store_id', '=', $storeId);
            })
            ->leftJoinSub($incomingSub, 'in_sum', function ($join) {
                $join->on('in_sum.product_id', '=', 'pr.id');
            })
            ->leftJoinSub($outgoingSub, 'out_sum', function ($join) {
                $join->on('out_sum.product_id', '=', 'pr.id');
            })
            ->leftJoin('tb_stock_opnames as so', function ($join) use ($storeId) {
                $join->on('so.product_id', '=', 'pr.id')
                     ->where('so.store_id', '=', $storeId);
            })
            ->select(
                'pr.id as product_id',
                'pr.product_name',
                's.id as store_id',
                's.store_name',
                DB::raw('COALESCE(sp.purchase_price, pr.purchase_price) as purchase_price'),
                DB::raw('COALESCE(sp.selling_price, pr.selling_price) as selling_price'),
                DB::raw('COALESCE(in_sum.total_in, 0) - COALESCE(out_sum.total_out, 0) as system_stock_raw'),
                DB::raw('so.physical_quantity as physical_quantity')
            )
            ->orderBy('pr.product_name')
            ->get();

        $stores = $getRoles === 'superadmin' ? tb_stores::all() : [];

        return view('pages.admin.inventory.index', compact('query', 'stores', 'storeId'));
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_name'       => 'required|string',
            'store_name'         => 'required|string',
            'physical_quantity'  => 'required|integer|min:0',
        ]);

        $product = tb_products::where('product_name', $request->product_name)->firstOrFail();
        $store   = tb_stores::where('store_name', $request->store_name)->firstOrFail();

        tb_stock_opnames::updateOrCreate(
            ['product_id' => $product->id, 'store_id' => $store->id],
            ['physical_quantity' => (int)$request->physical_quantity]
        );

        return response()->json(['message' => 'Stock opname berhasil disimpan']);
    }

    /**
     * Endpoint V3: FULL RAW SQL untuk write, tanpa Query Builder
     * Route: inventory.adjustStockBulkV3
     */
    public function adjustStockBulkV3(Request $request)
    {
        $ver = 'adj-v15-nobuilder';

        $items = $request->input('items');
        if (!$items) {
            $pids = (array)$request->input('product_id', []);
            $sids = (array)$request->input('store_id', []);
            $phys = (array)$request->input('physical_quantity', []);
            if (count($pids) !== count($sids) || count($pids) !== count($phys)) {
                return response()->json(['message' => "[$ver] Payload tidak valid"], 422);
            }
            $items = [];
            foreach ($pids as $i => $pid) {
                $items[] = [
                    'product_id'        => (int)$pid,
                    'store_id'          => (int)($sids[$i] ?? 0),
                    'physical_quantity' => max(0, (int)($phys[$i] ?? 0)),
                ];
            }
        }

        if (!is_array($items) || empty($items)) {
            return response()->json(['message' => "[$ver] Payload tidak valid (items)"], 422);
        }
        $items = array_values(array_map(function ($row) {
            if (is_object($row)) $row = (array)$row;
            return [
                'product_id'        => (int)($row['product_id'] ?? 0),
                'store_id'          => (int)($row['store_id'] ?? 0),
                'physical_quantity' => max(0, (int)($row['physical_quantity'] ?? 0)),
            ];
        }, $items));

        try {
            DB::transaction(function () use ($items, $ver) {
                $now = now();

                $storeIds = array_values(array_unique(array_filter(array_column($items, 'store_id'))));
                if (count($storeIds) !== 1) {
                    throw new \RuntimeException("[$ver] Multiple/invalid store_id");
                }
                $storeId = (int)$storeIds[0];

                $supplier = DB::select('SELECT id FROM tb_suppliers WHERE code = ? LIMIT 1', ['SO-ADJ']);
                $supplierId = $supplier ? (int)$supplier[0]->id : null;
                if (!$supplierId) {
                    DB::insert(
                        'INSERT INTO tb_suppliers (`code`,`name`,`address`,`created_at`,`updated_at`)
                         VALUES (?,?,?,?,?)',
                        ['SO-ADJ', 'Stock Opname Adjustment', null, $now, $now]
                    );
                    $supplierId = (int)DB::getPdo()->lastInsertId();
                }

                $productIds = array_values(array_unique(array_filter(array_column($items, 'product_id'))));
                if (empty($productIds)) return;

                $ph = fn($n) => implode(',', array_fill(0, $n, '?'));

                $paramsIn = array_merge([$storeId], $productIds);
                $rowsIn = DB::select(
                    'SELECT ig.product_id, SUM(ig.stock) AS total_in
                       FROM tb_incoming_goods ig
                       JOIN tb_purchases p ON ig.purchase_id = p.id
                      WHERE p.store_id = ? AND ig.product_id IN ('.$ph(count($productIds)).')
                   GROUP BY ig.product_id',
                    $paramsIn
                );
                $incoming = [];
                foreach ($rowsIn as $r) { $incoming[(int)$r->product_id] = (int)$r->total_in; }

                $paramsOut = array_merge([$storeId], $productIds);
                $rowsOut = DB::select(
                    'SELECT og.product_id, SUM(og.quantity_out) AS total_out
                       FROM tb_outgoing_goods og
                       JOIN tb_sells sl ON og.sell_id = sl.id
                      WHERE sl.store_id = ? AND og.product_id IN ('.$ph(count($productIds)).')
                   GROUP BY og.product_id',
                    $paramsOut
                );
                $outgoing = [];
                foreach ($rowsOut as $r) { $outgoing[(int)$r->product_id] = (int)$r->total_out; }

                $rowsPrice = DB::select(
                    'SELECT p.id, COALESCE(sp.purchase_price, p.purchase_price) AS purchase_price
                       FROM tb_products p
                       LEFT JOIN tb_product_store_prices sp
                         ON sp.product_id = p.id AND sp.store_id = ?
                      WHERE p.id IN ('.$ph(count($productIds)).')',
                    array_merge([$storeId], $productIds)
                );
                $prices = [];
                foreach ($rowsPrice as $r) { $prices[(int)$r->id] = (int)$r->purchase_price; }

                $purchaseId    = null;
                $totalPurchase = 0;

                foreach ($items as $it) {
                    $pid  = (int)$it['product_id'];
                    $phys = (int)$it['physical_quantity'];
                    if ($pid <= 0) continue;

                    $system = (int)($incoming[$pid] ?? 0) - (int)($outgoing[$pid] ?? 0);

                    $exists = DB::select(
                        'SELECT 1 FROM tb_stock_opnames WHERE product_id = ? AND store_id = ? LIMIT 1',
                        [$pid, $storeId]
                    );
                    if ($exists) {
                        DB::update(
                            'UPDATE tb_stock_opnames
                                SET physical_quantity = ?, updated_at = ?
                              WHERE product_id = ? AND store_id = ?',
                            [$phys, $now, $pid, $storeId]
                        );
                    } else {
                        DB::insert(
                            'INSERT INTO tb_stock_opnames
                              (`product_id`,`store_id`,`physical_quantity`,`created_at`,`updated_at`)
                              VALUES (?,?,?,?,?)',
                            [$pid, $storeId, $phys, $now, $now]
                        );
                    }

                    $plus = max(0, $phys - $system);
                    if ($plus > 0) {
                        if ($purchaseId === null) {
                            DB::insert(
                                'INSERT INTO tb_purchases (`supplier_id`,`store_id`,`total_price`,`created_at`,`updated_at`)
                                 VALUES (?,?,?,?,?)',
                                [$supplierId, $storeId, 0, $now, $now]
                            );
                            $purchaseId = (int)DB::getPdo()->lastInsertId();
                        }

                        $price = (int)($prices[$pid] ?? 0);
                        $totalPurchase += $plus * $price;

                        DB::insert(
                            'INSERT INTO tb_incoming_goods
                               (`purchase_id`,`product_id`,`stock`,`description`,`created_at`,`updated_at`)
                             VALUES (?,?,?,?,?,?)',
                            [$purchaseId, $pid, $plus, 'Stock Opname (+)', $now, $now]
                        );
                    }
                }

                if ($purchaseId !== null) {
                    DB::update(
                        'UPDATE tb_purchases SET total_price = ?, updated_at = ? WHERE id = ?',
                        [$totalPurchase, $now, $purchaseId]
                    );
                }
            });

            return response()->json([
                'message' => "[$ver] Stock opname tersimpan. Pembelian dibuat jika ada penambahan stok."
            ]);
        } catch (\Throwable $e) {
            Log::error('adjustStockBulkV3 error', [
                'ver' => $ver, 'err' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json([
                'message' => "[$ver] Gagal menyimpan: ".$e->getMessage()." @ ".$e->getFile().":".$e->getLine()
            ], 500);
        }
    }
}
