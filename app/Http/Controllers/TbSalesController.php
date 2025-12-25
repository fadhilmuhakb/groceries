<?php

namespace App\Http\Controllers;

use App\Models\tb_customers;
use App\Models\tb_incoming_goods;
use App\Models\tb_outgoing_goods;
use App\Models\tb_products;
use App\Models\tb_sell;
use App\Models\tb_stores;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class TbSalesController extends Controller
{
    public function index(Request $request)
    {
        $user_id = auth()->user()->id;
        $store_id = auth()->user()->roles === 'superadmin'
            ? $request->get('store_id')
            : auth()->user()->store_id;
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
            $product = tb_incoming_goods::with(['product.storePrices', 'purchase'])
                        ->when($store_id, fn($q) => $q->whereHas('purchase', fn($p) => $p->where('store_id', $store_id)))
                        ->get();
        }
        else if(auth()->user()->roles == 'staff' || auth()->user()->roles == 'admin') {
            $customers = tb_customers::where('store_id', auth()->user()->store_id)->get();

            $product = tb_incoming_goods::with(['product.storePrices', 'purchase'])
                                        ->whereHas('purchase', function($q) use ($store_id) {
                                            $q->where('store_id', $store_id);
                                        })->get();

        }

        $stores = tb_stores::all();
        if($request->ajax()) {
            $products = $product->map(function($row) use ($store_id) {
                            $pricing = optional($row->product)->priceForStore($store_id);
                            $row->price = $pricing['selling_price'] ?? 0;
                            $row->product_discount = $pricing['product_discount'] ?? 0;
                            $row->selling_price = ($pricing['selling_price'] ?? 0) - ($pricing['product_discount'] ?? 0);
                            $row->tier_prices = $pricing['tier_prices'] ?? null;
                            return $row;
                        });

            return DataTables::of($products)
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
        
        return view('pages.admin.sales.index', ['user' => $user, 'invoice_number' => $invoce_number, 'customers'=> $customers, 'stores' => $stores]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->data, [
            'transaction_date' => 'required',
            'customer_money' => 'required',
            'customer_id' => 'nullable',
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
            if($user->roles === 'superadmin') {
                $store_id = $request->data['store_id'];
            } else {
                $store_id = $user->store_id;
            }
            $storeOnline = \App\Models\tb_stores::where('id', $store_id)->value('is_online');
            $storeOnline = $storeOnline === null ? true : (bool) $storeOnline;
            $isPendingStock = !$storeOnline;
            $hasOutgoingStore = Schema::hasColumn('tb_outgoing_goods', 'store_id');
            $hasPendingStock = Schema::hasColumn('tb_outgoing_goods', 'is_pending_stock');

            $sell = tb_sell::create([
                'no_invoice' => $request->data['no_invoice'],
                'store_id' => $store_id,
                'date' => $request->data['transaction_date'],
                'total_price' => $request->data['total_price'],
                'payment_amount' => $request->data['customer_money'],
                'customer_id' => $request->data['customer_id'] ?? 0

            ]);

            foreach($request->data['products'] as $product) {
                $payload = [
                    'product_id' => $product['id'],
                    'sell_id' => $sell->id,
                    'date' => $request->data['transaction_date'],
                    'quantity_out' => $product['qty'],
                    'discount' => $product['discount'],
                    'recorded_by' => $user->name,
                    // 'description' => $product['description']
                ];
                if ($hasPendingStock) {
                    $payload['is_pending_stock'] = $isPendingStock;
                }
                if ($hasOutgoingStore) {
                    $payload['store_id'] = $store_id;
                }
                tb_outgoing_goods::create($payload);
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
