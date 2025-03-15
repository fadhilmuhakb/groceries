<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\tb_incoming_goods;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
    
        $tb_incoming_goods = DB::table('tb_incoming_goods as ig')
        ->join('tb_purchases as p', 'ig.purchase_id', '=', 'p.id') 
        ->join('tb_products as pr', 'ig.product_id', '=', 'pr.id') 
        ->join('tb_stores as s', 'p.store_id', '=', 's.id') 
        ->select(
            'pr.product_name as product_name',  
            's.store_name as store_name',      
            DB::raw('SUM(ig.stock) as total_stock') 
        )
        ->groupBy('pr.product_name', 's.store_name');
    
    
        if ($request->ajax()) {
            return DataTables::of($tb_incoming_goods->get()) 
                ->addColumn('action', function ($row) {
                    return '<a href="/inventory/edit/'.$row->product_name.'" class="btn btn-sm btn-success">
                                <i class="bx bx-pencil me-0"></i>
                            </a>
                            <a href="javascript:void(0)" onClick="confirmDelete(\''.$row->product_name.'\')" 
                                class="btn btn-sm btn-danger">
                                <i class="bx bx-trash me-0"></i>
                            </a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('pages.admin.inventory.index');
    }
}    