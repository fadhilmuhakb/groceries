<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\tb_stores;
use App\Models\tb_products;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $getRoles = $user->roles;

        $storeId = $getRoles === 'superadmin' ? $request->get('store_id') : $user->store_id;

        $query = DB::table('tb_incoming_goods as ig')
            ->join('tb_purchases as p', 'ig.purchase_id', '=', 'p.id')
            ->join('tb_products as pr', 'ig.product_id', '=', 'pr.id')
            ->join('tb_stores as s', 'p.store_id', '=', 's.id')
            ->leftJoin('tb_outgoing_goods as og', function ($join) {
                $join->on('ig.product_id', '=', 'og.product_id');
            })
            ->leftJoin('tb_stock_opnames as so', function ($join) {
                $join->on('ig.product_id', '=', 'so.product_id')
                    ->on('p.store_id', '=', 'so.store_id');
            })
            ->select(
                'pr.id as product_id',
                'pr.product_name',
                's.id as store_id',
                's.store_name',
                'pr.purchase_price',
                DB::raw('SUM(ig.stock) - COALESCE(SUM(og.quantity_out), 0) as system_stock'),
                DB::raw('COALESCE(so.physical_quantity, 0) as physical_stock')
            )
            ->when($storeId, fn($q) => $q->where('p.store_id', $storeId))
            ->groupBy('pr.id', 'pr.product_name', 's.id', 's.store_name', 'pr.purchase_price', 'so.physical_quantity')
            ->orderBy('pr.product_name')
            ->get();

        $stores = $getRoles === 'superadmin' ? tb_stores::all() : [];

        return view('pages.admin.inventory.index', compact('query', 'stores', 'storeId'));
    }


    // Simpan data stock opname
    public function adjustStock(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'store_name' => 'required|string',
            'physical_quantity' => 'required|integer|min:0',
        ]);

        // Cari product dan store id dari nama
        $product = tb_products::where('product_name', $request->product_name)->firstOrFail();
        $store = tb_stores::where('store_name', $request->store_name)->firstOrFail();

        // Simpan/update ke tabel tb_stock_opnames
        DB::table('tb_stock_opnames')->updateOrInsert(
            [
                'product_id' => $product->id,
                'store_id' => $store->id,
            ],
            [
                'physical_quantity' => $request->physical_quantity,
                'updated_at' => now(),
            ]
        );

        return response()->json(['message' => 'Stock opname berhasil disimpan']);
    }
    public function adjustStockBulk(Request $request)
    {
        $productIds = $request->input('product_id');
        $storeIds = $request->input('store_id');
        $physicalQuantities = $request->input('physical_quantity');

        try {
            foreach ($productIds as $i => $productId) {
                $storeId = $storeIds[$i];
                $physicalQty = $physicalQuantities[$i];

                DB::table('tb_stock_opnames')->updateOrInsert(
                    ['product_id' => $productId, 'store_id' => $storeId],
                    ['physical_quantity' => $physicalQty, 'updated_at' => now()]
                );
            }
            return response()->json(['message' => 'Stock opname berhasil disimpan']);
        } catch (\Exception $e) {
            // Kirim error message supaya bisa debug
            return response()->json(['message' => 'Gagal menyimpan stok opname: ' . $e->getMessage()], 500);
        }
    }


}
