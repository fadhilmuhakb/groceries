<?php

namespace App\Http\Controllers;

use App\Models\tb_products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TbProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = tb_products::all();
        if($request->ajax()) {
            DataTables::of($products)
            ->addColumn('action', function ($type) {
                return '<a href="/master-unit/edit/'.$type->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                </a>
                <a href="javascript:void(0)" onClick="confirmDelete('.$type->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                </a>
                ';
            })
            ->rawColumns((['action']))
            ->make(true);
        }

        return view('pages.admin.master.manage_product.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.master.manage_product.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_code' => 'required',
            'product_name' => 'required',
            'type_id' => 'required',
            'brand_id' => 'required',
            'unit_id' => 'required',
            'description' => 'nullable'
        ]);


        DB::beginTransaction();
        try {
            tb_products::create($data);
            DB::commit();
            return redirect('/master-product')->with('success', 'Data berhasil dikirim!');
        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = tb_products::where('id', $id)->first();
        return view('pages.admin.master.manage_product.create', ['product'=>$product]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'product_code' => 'required',
            'product_name' => 'required',
            'type_id' => 'required',
            'brand_id' => 'required',
            'unit_id' => 'required',
            'description' => 'nullable'
        ]);

        DB::beginTransaction();
        try {
            tb_products::where('id', $id)->update($data);
            DB::commit();
            return redirect('/master-product')->with('success', 'Data berhasil diperbaharui');
        }catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            tb_products::where('id',$id)->delete();
            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Produk berhasil dihapus',
            ]);
        }catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'=>false,
                'message'=>'Produk gagal dihapus',
            ]);
        }
    }
}
