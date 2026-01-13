<?php

namespace App\Http\Controllers;

use App\Models\tb_incoming_goods;
use App\Models\tb_products;
use App\Models\tb_stores;
use App\Models\tb_suppliers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class TbIncomingGoodsController extends Controller
{

    public function options(Request $request)
    {
        try {
            $search = $request->has('search_term') ? $request->search_term : $request->term;
            $draw   = $request->get('draw');
            $userId = auth()->user()->id;
            $user   = User::where('id', $userId)->with('store')->first();

            if(auth()->user()->roles == 'superadmin') {
                $storeId = (int)$request->get('store_id');
                if (!$storeId) {
                    if ($draw) {
                        return DataTables::of(collect())->make(true);
                    }
                    return response()->json(['success' => true, 'data' => []]);
                }
            } else {
                $storeId = auth()->user()->store_id;
            }

            $products = tb_products::with(['unit', 'type', 'brand', 'storePrices'])
                ->when($search, function($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('product_name', 'LIKE', '%'.$search.'%')
                          ->orWhere('product_code', 'LIKE','%'.$search.'%');
                    });
                })
                ->get();

            $stockMap = $this->buildStockMap($storeId, $products->pluck('id')->all());

            $products = $products
                ->map(function($product) use ($storeId, $stockMap) {
                    $product->current_stock = (int)($stockMap[$product->id] ?? 0);
                    $product->price_payload = $product->priceForStore($storeId);
                    return $product;
                })
                ->where('current_stock', '>', 0);

            $productsArray = $products->map(function($product) {
                $pricing = $product->price_payload ?? $product->priceForStore(null);
                return [
                    'id'               => $product->id,
                    'product_code'     => $product->product_code,
                    'product_name'     => $product->product_name,
                    'current_stock'    => $product->current_stock,
                    'unit_name'        => $product->unit->unit_name ?? '-',
                    'type_name'        => $product->type->type_name ?? '-',
                    'price'            => $pricing['selling_price'] ?? 0,
                    'product_discount' => $pricing['product_discount'] ?? 0,
                    'selling_price'    => ($pricing['selling_price'] ?? 0) - ($pricing['product_discount'] ?? 0),
                    'tier_prices'      => $pricing['tier_prices'] ?? null,
                    'brand_name'       => $product->brand->brand_name ?? '-',
                ];
            });

            if ($draw) {
                return DataTables::of($productsArray)->make(true);
            }

            return response()->json([
                'success' => true,
                "data" => array_values($productsArray->toArray())
            ]);

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
        if (Schema::hasColumn('tb_incoming_goods', 'is_pending_stock')) {
            $storeOnline = (int) tb_stores::where('id', $data['store_id'])->value('is_online') === 1;
            $data['is_pending_stock'] = $storeOnline ? 0 : 1;
        }

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
        if (Schema::hasColumn('tb_incoming_goods', 'is_pending_stock')) {
            $storeOnline = (int) tb_stores::where('id', $data['store_id'])->value('is_online') === 1;
            $data['is_pending_stock'] = $storeOnline ? 0 : 1;
        }

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

    /**
     * Hitung stok agregat per produk untuk satu toko (incoming - outgoing),
     * dengan dukungan kolom is_pending_stock serta fallback store_id di purchases.
     */
    private function buildStockMap(int $storeId, array $productIds): array
    {
        if (empty($productIds)) return [];

        $hasIncomingStore   = Schema::hasColumn('tb_incoming_goods', 'store_id');
        $hasPendingIn       = Schema::hasColumn('tb_incoming_goods', 'is_pending_stock');
        $hasPendingOut      = Schema::hasColumn('tb_outgoing_goods', 'is_pending_stock');
        $hasIncomingDeleted = Schema::hasColumn('tb_incoming_goods', 'deleted_at');
        $hasOutgoingDeleted = Schema::hasColumn('tb_outgoing_goods', 'deleted_at');

        $incoming = DB::table('tb_incoming_goods as ig')
            ->when($hasIncomingDeleted, fn($q) => $q->whereNull('ig.deleted_at'))
            ->when(
                $hasIncomingStore,
                fn($q) => $q->where(function ($qq) use ($storeId) {
                    $qq->where('ig.store_id', $storeId)
                       ->orWhereExists(function ($ex) use ($storeId) {
                           $ex->select(DB::raw(1))
                              ->from('tb_purchases as p')
                              ->whereColumn('p.id', 'ig.purchase_id')
                              ->where('p.store_id', $storeId);
                       });
                }),
                fn($q) => $q->join('tb_purchases as p', 'ig.purchase_id', '=', 'p.id')
                           ->where('p.store_id', $storeId)
            )
            ->whereIn('ig.product_id', $productIds)
            ->when($hasPendingIn, function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('ig.is_pending_stock')
                       ->orWhere('ig.is_pending_stock', 0);
                });
            })
            ->select('ig.product_id', DB::raw('SUM(ig.stock) as total_in'))
            ->groupBy('ig.product_id')
            ->pluck('total_in', 'product_id');

        $outgoing = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as sl', 'og.sell_id', '=', 'sl.id')
            ->when($hasOutgoingDeleted, fn($q) => $q->whereNull('og.deleted_at'))
            ->where('sl.store_id', $storeId)
            ->whereIn('og.product_id', $productIds)
            ->when($hasPendingOut, function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('og.is_pending_stock')
                       ->orWhere('og.is_pending_stock', 0);
                });
            })
            ->select('og.product_id', DB::raw('SUM(og.quantity_out) as total_out'))
            ->groupBy('og.product_id')
            ->pluck('total_out', 'product_id');

        $stockMap = [];
        foreach ($productIds as $pid) {
            $totalIn  = (int)($incoming[$pid] ?? 0);
            $totalOut = (int)($outgoing[$pid] ?? 0);
            $stockMap[$pid] = $totalIn - $totalOut;
        }

        return $stockMap;
    }
}
