<?php

namespace App\Http\Controllers;

use App\Models\tb_stores;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
class TbStoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $stores = tb_stores::all();
        if($request->ajax()) {
            return DataTables::of($stores)
                    ->addColumn('action', function ($stores) {
                        return '<a href="/store/edit/'.$stores->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                        </a>
                        <a href="javascript:void(0)" onClick="confirmDelete('.$stores->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                        </a>
                        ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('pages.admin.manage_store.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.manage_store.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_address' => 'required',
            'store_name' => 'required'
        ]);

        DB::beginTransaction();
        try {
            tb_stores::create($validated);
            DB::commit();
            return redirect('/store')->with('success', 'Data berhasil dikirim!');
        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(tb_stores $tb_stores)
    {
        //
    }

  
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $stores = tb_stores::findOrFail($id);
        return view('pages.admin.manage_store.create', ['stores' => $stores]); // ← ini harusnya edit.blade.php
    }
    

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'store_address' => 'required',
            'store_name' => 'required'
        ]);

        DB::beginTransaction();
        try {
            tb_stores::where('id', $id)->update($data);
            DB::commit();
            return redirect('/store')->with('success', 'Data berhasil diperbaharui!');
        } catch(\Exception $e) {
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
            tb_stores::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Toko berhasil dihapus',
            ]);
        }catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'=>false,
                'message'=>'Toko berhasil dihapus',
            ]);
        }
    
    }
}
