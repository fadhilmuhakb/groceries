<?php

namespace App\Http\Controllers;

use App\Models\tb_customers;
use App\Models\tb_incoming_goods;
use App\Models\tb_outgoing_goods;
use App\Models\tb_products;
use App\Models\tb_sell;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class TbSalesController extends Controller
{
    public function index(Request $request)
    {
        $user_id = auth()->user()->id;
        $current_month = Carbon::now()->format('m');
        $current_year = Carbon::now()->format('Y');
        $count_invoice = tb_sell::where('store_id', auth()->user()->store_id)
                                    ->whereMonth('date', $current_month)
                                    ->whereYear('date', $current_year)
                        ->count();
        $invoce_number = 'INV-'.$current_year.$current_month.str_pad($count_invoice+1, 4, '0', STR_PAD_LEFT);
        
        // dd($invoce_number);
        $user = User::where('id', $user_id)->with('store')->first();
        if(auth()->user()->roles == 'superadmin') {
            $customers = tb_customers::all();

            $product = tb_incoming_goods::with('product', 'purchase')->get();
        }
        else if(auth()->user()->roles == 'staff' || auth()->user()->roles == 'admin') {
            $customers = tb_customers::where('store_id', auth()->user()->store_id)->get();

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
        
        return view('pages.admin.sales.index', ['user' => $user, 'invoice_number' => $invoce_number, 'customers'=> $customers]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->data, [
            'transaction_date' => 'required',
            'customer_money' => 'required',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer',
            'products.*.qty' => 'required|integer|min:1',
        ]);

        if($validator->fails()) {
            // dd($validator->errors());
            return response()->json($validator->errors(), 422);
        }
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $sell = tb_sell::create([
                'no_invoice' => $request->data['no_invoice'],
                'store_id' => $user->store_id,
                'date' => $request->data['transaction_date'],
                'total_price' => $request->data['total_price'],
                'payment_amount' => $request->data['customer_money'],

            ]);

            foreach($request->data['products'] as $product) {
                tb_outgoing_goods::create([
                    'product_id' => $product['id'],
                    'sell_id' => $sell->id,
                    'date' => $request->data['transaction_date'],
                    'quantity_out' => $product['qty'],
                    'discount' => $product['discount'],
                    'recorded_by' => $user->name,
                    // 'description' => $product['description']
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil di proses'
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
