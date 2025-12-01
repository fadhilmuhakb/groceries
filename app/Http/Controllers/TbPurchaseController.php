<?php

namespace App\Http\Controllers;

use App\Models\tb_purchase;
use App\Models\tb_suppliers;
use App\Models\tb_products;
use App\Models\tb_incoming_goods;
use App\Models\tb_stores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TbPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $purchases = tb_purchase::with(relations: ['supplier','store'])->get();


        if ($request->ajax()) {
            return DataTables::of($purchases)
            ->addColumn('action', function ($purchases) {
                return '
                <div class="d-flex justify-content-center">
                    <a href="/purchase/edit/'.$purchases->id.'" class="btn btn-sm btn-success me-1">
                       Edit <i class="bx bx-right-arrow-alt"></i> 
                    </a>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
        }
        return view('pages.admin.purchase.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = tb_suppliers::all();
        $products = tb_products::all();
        $stores = tb_stores::all(); 
    
     
    
        return view('pages.admin.purchase.create', compact('suppliers', 'products', 'stores'));
    }
    
    public function store(Request $request)
{
    DB::beginTransaction();
    try {
        // Simpan ke tb_purchase
        $purchase = tb_purchase::create([
            'supplier_id' => $request->supplier_id,
            'store_id' => $request->store_id,
            'total_price' => $request->total_price,
        ]);
        $storeOnline = \App\Models\tb_stores::where('id', $request->store_id)->value('is_online');
        $isPendingStock = !$storeOnline;
        
        // Simpan produk ke tb_incoming_goods
        foreach ($request->products as $product) {
            tb_incoming_goods::create([
                'purchase_id' => $purchase->id, // ✅ Perbaikan: tambahkan purchase_id
                'product_id' => $product['product_id'],
                'stock' => $product['stock'],
                'description' => $product['description'] ?? null, // Jika null, tetap bisa disimpan
                'is_pending_stock' => $isPendingStock,
            ]);
        }

        DB::commit();
        return redirect()->route('purchase.index')->with('success', 'Data pembelian berhasil disimpan!');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
    }
}

    
    
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $purchase = tb_purchase::with('incomingGoods')->findOrFail($id);

        $suppliers = tb_suppliers::all();
        $stores = tb_stores::all();
        $products = tb_products::all();
    
        return view('pages.admin.purchase.edit', compact('purchase', 'suppliers', 'stores', 'products'));
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Ambil data pembelian yang ingin diperbarui
            $purchase = tb_purchase::findOrFail($id);
    
            // Update data pembelian
            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'store_id' => $request->store_id,
                'total_price' => $request->total_price,
            ]);
    
            // Hapus semua produk lama sebelum menyimpan yang baru
            tb_incoming_goods::where('purchase_id', $id)->delete();
    
            // Simpan produk baru yang diinputkan user
            foreach ($request->products as $product) {
                tb_incoming_goods::create([
                    'purchase_id' => $id,
                    'product_id' => $product['product_id'],
                    'stock' => $product['stock'],
                    'description' => $product['description'] ?? null,
                ]);
            }
    
            DB::commit();
            return redirect()->route('purchase.index')->with('success', 'Pembelian berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            tb_purchase::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus data',
            ]);
        }
    }
}
