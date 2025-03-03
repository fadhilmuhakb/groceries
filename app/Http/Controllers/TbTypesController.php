<?php

namespace App\Http\Controllers;

use App\Models\tb_types;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TbTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $types = tb_types::all();
        if($request->ajax()) {
            return DataTables::of($types)
                    ->addColumn('action', function ($type) {
                        return '<a href="/master-type/edit/'.$type->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                        </a>
                        <a href="/master-type/delete/'.$type->id.'" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                        </a>
                        ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('pages.admin.master.manage_type.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.admin.master.manage_type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_name' => 'required',
            'description' => 'nullable'
        ]);


        return back()->with('success', 'Form berhasil dikirim!');
    }

    /**
     * Display the specified resource.
     */
    public function show(tb_types $tb_types)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(tb_types $tb_types)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, tb_types $tb_types)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(tb_types $tb_types)
    {
        //
    }
}
