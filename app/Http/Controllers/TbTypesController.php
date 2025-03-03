<?php

namespace App\Http\Controllers;

use App\Models\tb_types;
use Illuminate\Http\Request;

class TbTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pages.admin.master.manage_type.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
