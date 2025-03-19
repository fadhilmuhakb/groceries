<?php

namespace App\Http\Controllers;

use App\Models\tb_incoming_goods;
use App\Models\tb_products;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TbSalesController extends Controller
{
    public function index(Request $request)
    {
        $user_id = auth()->user()->id;
        $user = User::where('id', $user_id)->with('store')->first();
        if(auth()->user()->roles == 'superadmin') {
            $product = tb_incoming_goods::with('product', 'purchase')->get();
        }
        else if(auth()->user()->roles == 'staff') {
            $product = tb_incoming_goods::with('product', 'purchase')
                                        ->whereHas('purchase', function($q) {
                                            $q->where('store_id', auth()->user()->store_id);
                                        })->get();

        }
        if($request->ajax()) {
            return DataTables::of($product)
                        ->addColumn('action', function($product) {
                            $productData = htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8');
                            return '<a href="javascript:void(0)" onClick="handleSelect('.$productData.')" class="btn btn-sm
                                    btn-success"><i class="lni lni-circle-plus me-0"></i>
                                    </a>
                                    ';
                        })
                        ->rawColumns(['action'])
                        ->make(true);
        };
        return view('pages.admin.sales.index', ['user' => $user]);
    }
}
