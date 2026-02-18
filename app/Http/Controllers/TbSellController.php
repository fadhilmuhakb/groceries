<?php

namespace App\Http\Controllers;

use App\Models\tb_sell;
use App\Models\tb_products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
                    <a href="/sell/edit/' . $sells->id . '" class="btn btn-sm btn-primary me-1">
                       Edit
                    </a>
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
    public function edit($id)
    {
        return $this->editById($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        return $this->updateById($request, $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(tb_sell $tb_sell)
    {
        //
    }

    public function editById($id)
    {
        $user = auth()->user();
        $role = strtolower((string) ($user->roles ?? ''));
        $allowed = store_access_ids($user);

        $sell = tb_sell::with(['store', 'outgoing_goods.product'])
            ->when($role !== 'superadmin', function ($query) use ($allowed) {
                if (!empty($allowed)) {
                    $query->whereIn('store_id', $allowed);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->findOrFail($id);

        $outgoingGoods = $sell->outgoing_goods;

        return view('pages.admin.sell.edit', compact('sell', 'outgoingGoods'));
    }

    public function updateById(Request $request, $id)
    {
        $user = auth()->user();
        $role = strtolower((string) ($user->roles ?? ''));
        $allowed = store_access_ids($user);

        $sell = tb_sell::with(['outgoing_goods.product'])
            ->when($role !== 'superadmin', function ($query) use ($allowed) {
                if (!empty($allowed)) {
                    $query->whereIn('store_id', $allowed);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $items = $request->input('items', []);
            $oldTotal = (float) $sell->total_price;
            $sell->date = $request->input('date');
            $sell->save();

            $totalPrice = 0;
            foreach ($items as $outgoingId => $item) {
                $qty = (int) ($item['qty'] ?? 0);
                $outgoing = $sell->outgoing_goods->firstWhere('id', (int) $outgoingId);
                if (!$outgoing) {
                    throw new \Exception('Item penjualan tidak ditemukan.');
                }

                $outgoing->quantity_out = $qty;
                $outgoing->date = $sell->date;
                $outgoing->save();

                $product = $outgoing->product;
                if ($product) {
                    $unitPrice = $this->resolveSellingPrice($product, (int) $sell->store_id, $qty);
                    $discount = (float) ($outgoing->discount ?? 0);
                    $totalPrice += ($unitPrice * $qty) - $discount;
                }
            }

            $sell->total_price = $totalPrice;
            if (abs(((float) $sell->payment_amount) - $oldTotal) < 0.0001) {
                $sell->payment_amount = $totalPrice;
            }
            $sell->save();

            DB::commit();
            return redirect()->route('sell.detail', $sell->id)->with('success', 'Penjualan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    private function resolveSellingPrice(tb_products $product, int $storeId, int $qty): float
    {
        $pricing = $product->priceForStore($storeId);
        $base = (float) ($pricing['selling_price'] ?? 0);
        $productDiscount = (float) ($pricing['product_discount'] ?? 0);
        $unitPrice = $base - $productDiscount;

        $tiers = $pricing['tier_prices'] ?? null;
        if (is_array($tiers) && !empty($tiers)) {
            $tiers = collect($tiers)
                ->mapWithKeys(fn ($price, $minQty) => [(int) $minQty => (float) $price])
                ->sortKeys();
            foreach ($tiers as $minQty => $tierPrice) {
                if ($qty >= $minQty) {
                    $unitPrice = (float) $tierPrice;
                }
            }
        }

        return $unitPrice;
    }
}
