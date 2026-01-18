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
        $role = strtolower((string) ($user->roles ?? ''));
        $query = tb_sell::with('store')
            ->orderByDesc('id');
        if ($role !== 'superadmin') {
            $allowed = store_access_ids($user);
            $query->when(!empty($allowed), fn ($q) => $q->whereIn('store_id', $allowed))
                ->when(empty($allowed), fn ($q) => $q->whereRaw('1 = 0'));
        }

        if ($request->ajax()) {
            return DataTables::eloquent($query)
                ->filterColumn('store.store_name', function ($query, $keyword) {
                    $query->whereHas('store', function ($q) use ($keyword) {
                        $q->where('store_name', 'like', '%' . $keyword . '%');
                    });
                })
                ->orderColumn('store.store_name', function ($query, $order) {
                    $query->leftJoin('tb_stores as stores', 'tb_sells.store_id', '=', 'stores.id')
                        ->orderBy('stores.store_name', $order)
                        ->select('tb_sells.*');
                })
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
        $role = strtolower((string) ($user->roles ?? ''));
        $allowed = store_access_ids($user);

        $sell = tb_sell::with('store')
            ->when($role !== 'superadmin', function ($query) use ($allowed) {
                if (!empty($allowed)) {
                    $query->whereIn('store_id', $allowed);
                } else {
                    $query->whereRaw('1 = 0');
                }
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
