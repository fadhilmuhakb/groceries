<?php

namespace App\Http\Controllers;

use App\Models\tb_units;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
class TbUnitsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $types = tb_units::all();
        if($request->ajax()) {
            return DataTables::of($types)
                    ->addColumn('action', function ($type) {
                        return '<a href="/master-unit/edit/'.$type->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                        </a>
                        <a href="javascript:void(0)" onClick="confirmDelete('.$type->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                        </a>
                        ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('pages.admin.master.manage_unit.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.master.manage_unit.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_name' => 'required',
            'description' => 'nullable'
        ]);

        DB::beginTransaction();
        try {
            tb_units::create($validated);
            DB::commit();
            return redirect('/master-unit')->with('success', 'Data berhasil dikirim!');
        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(tb_units $tb_units)
    {
        //
    }

  
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $unit = tb_units::where('id', $id)->first();
        return view('pages.admin.master.manage_unit.create', ['unit'=>$unit]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'unit_name' => 'required',
            'description' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            tb_units::where('id', $id)->update($data);
            DB::commit();
            return redirect('/master-unit')->with('success', 'Data berhasil diperbaharui!');
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
            tb_units::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Unit berhasil dihapus',
            ]);
        }catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'=>false,
                'message'=>'Unit berhasil dihapus',
            ]);
        }
    
    }
}
