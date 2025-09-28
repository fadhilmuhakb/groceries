<?php

namespace App\Http\Controllers;

use App\Models\tb_suppliers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TbSuppliersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $suppliers = tb_suppliers::all();
        if($request->ajax()) {
            return DataTables::of($suppliers)
                    ->addColumn('action', function ($supplier) {
                        return '<a href="/supplier/edit/'.$supplier->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                        </a>
                        <a href="javascript:void(0)" onClick="confirmDelete('.$supplier->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                        </a>
                        ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }

        return view('pages.admin.manage_supplier.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.manage_supplier.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'city' => 'nullable',
            'province' => 'nullable',
            'phone_number' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            tb_suppliers::create($data);
            DB::commit();
            return redirect()->route('supplier.index')->with('success', 'Supplier berahasil dibuat');
        }catch(\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $supplier = tb_suppliers::where('id', $id)->first();
        return view('pages.admin.manage_supplier.create', ['supplier'=>$supplier]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'code' => 'required',
            'name' => 'required',
            'address' => 'required',
            'city' => 'nullable',
            'province' => 'nullable',
            'phone_number' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            tb_suppliers::where('id', $id)->update($data);
            DB::commit();
            return redirect()->route('supplier.index')->with('success', 'Supplier berahasil diperbaharui');
        }catch(\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            tb_suppliers::where('id', $id)->delete();
            
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Supplier berhasil dihapus']);
        }catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'success', 'message' => 'Supplier berhasil dihapus']);
            
        }
    }
}
