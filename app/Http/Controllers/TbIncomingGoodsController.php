<?php

namespace App\Http\Controllers;

use App\Models\tb_incoming_goods;
use App\Models\tb_products;
use App\Models\tb_stores;
use App\Models\tb_suppliers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TbIncomingGoodsController extends Controller
{

    public function options(Request $request)
    {
        try {

            if($request->has('search_term')) {
                $search = $request->search_term;
            } else {
                $search = $request->term;
            }
            $user_id = auth()->user()->id;
            $user = User::where('id', $user_id)->with('store')->first();
            if(auth()->user()->roles == 'superadmin') {

                $products = tb_products::with(['incomingGoods', 'outgoingGoods','unit', 'type','brand'])
                    ->when($search, function($query) use($request, $search) {
                        $query->where(function($q) use($request, $search) {
                            $q->where('product_name', 'LIKE', '%'.$search.'%')
                                ->orWhere('product_code', 'LIKE','%'.$search.'%');
                        });
                    })
                    ->get()
                    ->map(function($product) {
                        $totalIncoming = $product->incomingGoods->sum('stock');
                        $totalOutgoing = $product->outgoingGoods->sum('quantity_out');
                        $product->current_stock = $totalIncoming - $totalOutgoing;
                        return $product;
                    })
                    ->where('current_stock', '>', 0);
            }
            else if(auth()->user()->roles == 'staff' || auth()->user()->roles == 'admin') {
                // $products = tb_incoming_goods::with('product', 'purchase')
                //                             ->whereHas('purchase', function($q) {
                //                                 $q->where('store_id', auth()->user()->store_id);
                //                             })
                //                             ->when($search, function($q1) use($request) {
                //                                 $q1->whereRelation('product', 'product_name', 'LIKE', '%'.$search.'%')
                //                                     ->orWhereRelation('product', 'product_code', 'LIKE','%'.$search.'%');
                //                             })
                //                             ->get();


                $products = tb_products::with(['incomingGoods' => function($query) {
                    $query->whereHas('purchase', function($q) {
                        $q->where('store_id', auth()->user()->store_id);
                    });
                    }
                    ])
                    ->with(['outgoingGoods' => function($query) {
                        $query->whereHas('sell', function($q) {
                            $q->where('store_id', auth()->user()->store_id);
                        });
                    }])
                    ->when($search, function($query) use($request, $search) {
                        $query->where(function($q) use($request, $search) {
                            $q->where('product_name', 'LIKE', '%'.$search.'%')
                                ->orWhere('product_code', 'LIKE','%'.$search.'%');
                        });
                    })
                    ->get()
                    ->map(function($product) {
                        $totalIncoming = $product->incomingGoods->sum('stock');
                        $totalOutgoing = $product->outgoingGoods->sum('quantity_out');
                        $product->current_stock = $totalIncoming - $totalOutgoing;
                        return $product;
                    })
                    ->where('current_stock', '>', 0);

            }

            // dd($products);

            // if($request->type === 'barcode') {
                $products = $products->map(function($product) {
                    return [
                        'id' => $product->id,
                        'product_code' => $product->product_code,
                        'product_name' => $product->product_name,
                        'current_stock' => $product->current_stock,
                        'unit_name' => $product->unit->unit_name ?? '-',
                        'type_name' => $product->type->type_name ?? '-',
                        'selling_price' => $product->selling_price,
                        'brand_name' => $product->brand->brand_name ?? '-',
                    ];
                });

                // dd($products);

                return response()->json([
                    'success' => true,
                    'data' => $products
                ]);
                
            // } else {
            //     $draw = intval($request->input('draw'));
            //     $start = intval($request->input('start'));
            //     $length = intval($request->input('length'));

            //     $total = $products->count();
            //     $pagedData = $products->slice($start, $length)->values();

            //     return response()->json([
            //         'draw' => $draw,
            //         'recordsTotal' => $total,
            //         'recordsFiltered' => $total,
            //         'data' => $pagedData->map(function($product) {
            //             return [
            //                 'id' => $product->id,
            //                 'product_code' => $product->product_code,
            //                 'product_name' => $product->product_name,
            //                 'current_stock' => $product->current_stock,
            //                 'unit_name' => $product->unit->unit_name ?? '-',
            //                 'type_name' => $product->type->type_name ?? '-',
            //                 'selling_price' => $product->selling_price,
            //                 'brand_name' => $product->brand->brand_name ?? '-',
            //             ];
            //         }),
            //     ]);
            // }
            // Pagination parameter dari DataTables
            

            // $options = [];
            // foreach($products as $product) {
            //     $options[] = [
            //         'id' => $product->id,
            //         'product_code' => $product->product_code,
            //         'product_name' => $product->product_name,
            //         'current_stock' => $product->current_stock,
            //         'unit_name' => $product->unit->unit_name,
            //         'type_name' => $product->type->type_name,
            //         'selling_price' => $product->selling_price,
            //         'brand_name' => $product->brand->brand_name,
            //     ];
            // }

            

        }catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function index(Request $request)
    {
        $incomingGoods = tb_incoming_goods::all();
        if($request->ajax()) {
            DataTables::of($incomingGoods)
                        ->addColumn('action', function($incomingGood) {
                            return '<a href="/incoming-goods/edit/'.$incomingGood->id.'" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i>
                                    </a>
                                    <a href="javascript:void(0)" onClick="confirmDelete('.$incomingGood->id.')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i>
                                    </a>
                                    ';
                        })
                        ->rawColumns(['action'])
                        ->make(true);
        }

        return view('pages.admin.manage_incoming_good.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()

    {
        $stores = tb_stores::all();
        $products = tb_products:: all();
        $suppliers = tb_suppliers::all();
        return view('pages.admin.manage_incoming_good.create', [
                                                                'stores' => $stores,
                                                                'products' => $products,
                                                                'suppliers' => $suppliers
                                                            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required',
            'supplier_id' => 'required',
            'store_id' => 'required',
            'stock' => 'required',
            'type' => 'required',
            'description' => 'nullable',
            'paid_of_date' => 'required|date'
        ]);

        DB::beginTransaction();
        try {
            tb_incoming_goods::create($data);
            DB::commit();
            return redirect()->route('incoming-goods.index')->with('success', 'Barang masuk berhasil dibuat');
        } catch(\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $stores = tb_stores::all();
        $products = tb_products:: all();
        $suppliers = tb_suppliers::all();
        $incomingGood = tb_incoming_goods::where('id', $id)->first();

        return view('pages.admin.manage_incoming_good.create', [
                                                                'incomingGood' => $incomingGood,
                                                                'stores' => $stores,
                                                                'products' => $products,
                                                                'suppliers' => $suppliers
                                                                ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'product_id' => 'required',
            'supplier_id' => 'required',
            'store_id' => 'required',
            'stock' => 'required',
            'type' => 'required',
            'description' => 'nullable',
            'paid_of_date' => 'required|date'
        ]);

        DB::beginTransaction();
        try {
            tb_incoming_goods::where('id', $id)->update($data);
            DB::commit();
            return redirect()->route('incoming-goods.index')->with('success', 'Barang masuk berhasil dibuat');
        } catch(\Exception $e){
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            tb_incoming_goods::where('id', $id)->delete();
            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Produk berhasil dihapus',
            ]);
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'=>false,
                'message'=>'Produk gagal dihapus',
            ]);
        }
    }
}
