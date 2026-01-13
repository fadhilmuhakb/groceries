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
        if ($user->roles == 'superadmin') {
            $sells = tb_sell::with('store')
                ->orderByDesc('id')
                ->get();
        } else {
            $sells = tb_sell::with('store')
                ->where('store_id', $user->store_id)
                ->orderByDesc('id')
                ->get();
        }

        if ($request->ajax()) {
            return DataTables::of($sells)
                ->addColumn('action', function ($sells) {
                    return '
                <div class="d-flex justify-content-center">
                    <a href="/sell/detail/' . $sells->id . '" class="btn btn-sm btn-success me-1">
                       Detail Penjualan <i class="bx bx-right-arrow-alt"></i> 
                    </a>
                </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.admin.sell.index');
    }

    public function detail($id)
    {
        $user = auth()->user();

        $sell = tb_sell::with('store')
            ->when($user->roles != 'superadmin', function ($query) use ($user) {
                $query->where('store_id', $user->store_id);
            })
            ->findOrFail($id);

        $outgoingGoods = \App\Models\tb_outgoing_goods::with('product')
            ->where('sell_id', $sell->id)
            ->get();

        return view('pages.admin.sell.detail', compact('sell', 'outgoingGoods'));
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
