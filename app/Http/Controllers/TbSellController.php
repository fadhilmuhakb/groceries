<?php

namespace App\Http\Controllers;

use App\Models\tb_sell;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TbSellController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if($user->roles == 'superadmin') {
            $sells = tb_sell::with('store')->get();
        } else {
            $sells = tb_sell::with('store')
                            ->where('store_id', $user->store_id)
                            ->get();
        }

        if($request->ajax()) {
            return DataTables::of($sells)
            ->addColumn('action', function ($sells) {
                return '
                <div class="d-flex justify-content-center">
                    <a href="/purchase/edit/'.$sells->id.'" class="btn btn-sm btn-success me-1">
                       Edit <i class="bx bx-right-arrow-alt"></i> 
                    </a>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
        }

        return view('pages.admin.sell.index');
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
    public function show(tb_sell $tb_sell)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(tb_sell $tb_sell)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, tb_sell $tb_sell)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(tb_sell $tb_sell)
    {
        //
    }
}
