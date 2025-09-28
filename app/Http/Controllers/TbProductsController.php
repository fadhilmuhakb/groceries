<?php

namespace App\Http\Controllers;

use App\Models\tb_products;
use App\Models\tb_types;
use App\Models\tb_brands;
use App\Models\tb_units;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TbProductsController extends Controller
{
    public function index(Request $request)
{
    if ($request->ajax()) {
        $rows = DB::table('tb_products as p')
            ->leftJoin('tb_types  as t', 't.id',  '=', 'p.type_id')
            ->leftJoin('tb_brands as b', 'b.id',  '=', 'p.brand_id')
            ->leftJoin('tb_units  as u', 'u.id',  '=', 'p.unit_id')
            ->selectRaw('
                p.id,
                p.product_code,
                p.product_name,
                COALESCE(t.type_name,  "-")  as type_name,
                COALESCE(b.brand_name, "-")  as brand_name,
                COALESCE(u.unit_name, "-")  as unit_name,
                p.purchase_price,
                p.selling_price,
                p.tier_prices,
                COALESCE(p.description, "-") as description
            ')
            ->orderByDesc('p.id')
            ->get();

        // format standard DataTables (simple) — tanpa paging server-side
        return response()->json([
            'data' => $rows,
        ]);
    }

    return view('pages.admin.master.manage_product.index');
}

    public function create()
    {
        return view('pages.admin.master.manage_product.create', [
            'types'  => tb_types::all(),
            'brands' => tb_brands::all(),
            'units'  => tb_units::all(),
        ]);
    }

  public function store(Request $request)
{
    // 1) Bersihkan baris tier kosong (qty & price dua-duanya kosong/null)
    $tiers = collect($request->input('tier_prices', []))
        ->filter(function ($row) {
            return ($row['qty'] ?? '') !== '' || ($row['price'] ?? '') !== '';
        })
        ->values()
        ->all();

    // merge kembali hasil bersih
    $request->merge(['tier_prices' => $tiers]);

    // 2) Validasi — sekarang aman karena kalau kosong, array-nya [] (tidak memicu nested rules)
    $data = $request->validate([
        'product_code'     => 'required|string|max:255|unique:tb_products,product_code',
        'product_name'     => 'required|string|max:255',
        'type_id'          => 'required|exists:tb_types,id',
        'brand_id'         => 'required|exists:tb_brands,id',
        'unit_id'          => 'required|exists:tb_units,id',
        'purchase_price'   => 'required|numeric|min:0',
        'selling_price'    => 'required|numeric|min:0',
        'product_discount' => 'nullable|numeric|min:0',
        'description'      => 'nullable|string',
        'tier_prices'          => 'nullable|array',
        'tier_prices.*.qty'    => 'required|integer|min:1',
        'tier_prices.*.price'  => 'required|numeric|min:0',
    ]);

    // 3) Normalisasi ke map qty=>price kalau ada isinya
    if (!empty($data['tier_prices'])) {
        $map = [];
        foreach ($data['tier_prices'] as $row) {
            $map[(int)$row['qty']] = (float)$row['price'];
        }
        ksort($map);
        $data['tier_prices'] = $map;
    }

    DB::beginTransaction();
    try {
        tb_products::create($data);
        DB::commit();
        return redirect()->route('master-product.index')->with('success', 'Produk berhasil ditambahkan');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withInput()->with('error', $e->getMessage());
    }
}


    public function edit($id)
    {
        return view('pages.admin.master.manage_product.create', [
            'product' => tb_products::findOrFail($id),
            'types'   => tb_types::all(),
            'brands'  => tb_brands::all(),
            'units'   => tb_units::all(),
        ]);
    }

   public function update(Request $request, $id)
{
    $tiers = collect($request->input('tier_prices', []))
        ->filter(function ($row) {
            return ($row['qty'] ?? '') !== '' || ($row['price'] ?? '') !== '';
        })
        ->values()
        ->all();

    $request->merge(['tier_prices' => $tiers]);

    $data = $request->validate([
        'product_code'     => 'required|string|max:255|unique:tb_products,product_code,'.$id,
        'product_name'     => 'required|string|max:255',
        'type_id'          => 'required|exists:tb_types,id',
        'brand_id'         => 'required|exists:tb_brands,id',
        'unit_id'          => 'required|exists:tb_units,id',
        'purchase_price'   => 'required|numeric|min:0',
        'selling_price'    => 'required|numeric|min:0',
        'product_discount' => 'nullable|numeric|min:0',
        'description'      => 'nullable|string',
        'tier_prices'          => 'nullable|array',
        'tier_prices.*.qty'    => 'required|integer|min:1',
        'tier_prices.*.price'  => 'required|numeric|min:0',
    ]);

    if (!empty($data['tier_prices'])) {
        $map = [];
        foreach ($data['tier_prices'] as $row) {
            $map[(int)$row['qty']] = (float)$row['price'];
        }
        ksort($map);
        $data['tier_prices'] = $map;
    } else {
        $data['tier_prices'] = null; // benar-benar kosong
    }

    DB::beginTransaction();
    try {
        tb_products::findOrFail($id)->update($data);
        DB::commit();
        return redirect()->route('master-product.index')->with('success', 'Produk berhasil diperbarui');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withInput()->with('error', $e->getMessage());
    }
}

    public function show($id)
    {
        $product = tb_products::find($id);
        abort_if(!$product, 404);
        return view('pages.admin.master.manage_product.preview', compact('product'));
    }

    public function destroy($id)
    {
        try {
            tb_products::findOrFail($id)->delete();
            return response()->json(['message' => 'Produk dihapus']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
