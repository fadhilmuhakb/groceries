<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\tb_products;
use App\Models\tb_types;
use App\Models\tb_brands;
use App\Models\tb_units;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TbProductsController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        // dd($user->roles);
        if($user->roles == 'superadmin') {
            $products = tb_products::with(['type', 'brand', 'unit'])->get();
            // dd($products);
        } else {
            $products = tb_products::with(['incomingGoods' => function($query) {
                $query->whereHas('purchase', function($q) {
                    $q->where('store_id', auth()->user()->store_id);
                });
            },
            'outgoingGoods' => function($query) {
                $query->whereHas('sell', function($q) {
                    $q->where('store_id', auth()->user()->store_id);
                });
            },
            'type', 'brand', 'unit'])
            ->get()
            ->map(function($product) {
                $totalIncoming = $product->incomingGoods->sum('stock');
                $totalOutgoing = $product->outgoingGoods->sum('quantity_out');
                $product->current_stock = $totalIncoming - $totalOutgoing;
                return $product;
            })
            ->where('current_stock', '>', 0);
        }

        if ($request->ajax()) {
            return DataTables::of($products)
                ->addColumn('type_name', function ($product) {
                    return $product->type->type_name ?? '-';
                })
                ->addColumn('brand_name', function ($product) {
                    return $product->brand->brand_name ?? '-';
                })
                ->addColumn('unit_name', function ($product) {
                    return $product->unit->unit_name ?? '-';
                })
                ->addColumn('action', function ($product) {
                    return '<a href="/master-product/edit/' . $product->id . '" class="btn btn-sm btn-success"><i class="bx bx-pencil me-0"></i></a>
                            <a href="javascript:void(0)" onClick="confirmDelete(' . $product->id . ')" class="btn btn-sm btn-danger"><i class="bx bx-trash me-0"></i></a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.admin.master.manage_product.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_code' => 'required',
            'product_name' => 'required',
            'type_id' => 'required|exists:tb_types,id',
            'brand_id' => 'required|exists:tb_brands,id',
            'unit_id' => 'required|exists:tb_units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable'
        ]);

        DB::beginTransaction();
        try {
            tb_products::create($data);
            DB::commit();
            return redirect('/master-product')->with('success', 'Data berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $file = $request->file('file');
        $data = Excel::toArray([], $file)[0];
        array_shift($data);

        $importedProducts = [];
        foreach ($data as $row) {
            $typeName = trim($row[2] ?? '');
            $brandName = trim($row[3] ?? '');
            $unitName = trim($row[4] ?? '');

            $type = $typeName ? tb_types::firstOrCreate(
                ['type_name' => $typeName],
                ['description' => $typeName]
            ) : null;

            $brand = $brandName ? tb_brands::firstOrCreate(
                ['brand_name' => $brandName],
                ['description' => $brandName]
            ) : null;

            $unit = $unitName ? tb_units::firstOrCreate(
                ['unit_name' => $unitName],
                ['description' => $unitName]
            ) : null;

            $purchasePrice = is_numeric($row[5] ?? null) ? (float) $row[5] : 0;
            $sellingPrice = is_numeric($row[6] ?? null) ? (float) $row[6] : 0;
            $importedProducts[] = [
                'product_code' => $row[0] ?? '',
                'product_name' => $row[1] ?? '',
                'type_id' => $type->id ?? null,
                'brand_id' => $brand->id ?? null,
                'unit_id' => $unit->id ?? null,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'description' => $row[7] ?? ''
            ];
        }

        session(['imported_products' => $importedProducts]);
        session()->save();
        return view('pages.admin.master.manage_product.preview', data: compact('importedProducts'));
    }



    public function saveImported()
    {
        $importedProducts = session('imported_products', []);
        $failedImports = [];

        foreach ($importedProducts as $product) {
            try {
                tb_products::create([
                    'product_code' => $product['product_code'],
                    'product_name' => $product['product_name'],
                    'type_id' => $product['type_id'],
                    'brand_id' => $product['brand_id'],
                    'unit_id' => $product['unit_id'],
                    'purchase_price' => $product['purchase_price'] ?? 0,
                    'selling_price' => $product['selling_price'] ?? 0,
                    'description' => $product['description'] ?? '',
                ]);
            } catch (\Exception $e) {

                $failedImports[] = $product;
            }
        }

        session()->forget('imported_products');


        if (!empty($failedImports)) {
            session(['failed_imports' => $failedImports]);
            return redirect()->route('master-product.index')->with('warning', 'Beberapa produk gagal diimpor.');
        }

        return redirect()->route('master-product.index')->with('success', 'Produk berhasil diimpor!');
    }


    public function create()
    {
        return view('pages.admin.master.manage_product.create', [
            'types' => tb_types::all(),
            'brands' => tb_brands::all(),
            'units' => tb_units::all()
        ]);
    }
    public function edit($id)
    {
        $product = tb_products::findOrFail($id);
        return view('pages.admin.master.manage_product.create', [
            'product' => $product,
            'types' => tb_types::all(),
            'brands' => tb_brands::all(),
            'units' => tb_units::all()
        ]);
    }
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'product_code' => 'required',
            'product_name' => 'required',
            'type_id' => 'required|exists:tb_types,id',
            'brand_id' => 'required|exists:tb_brands,id',
            'unit_id' => 'required|exists:tb_units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable'
        ]);

        DB::beginTransaction();
        try {
            $product = tb_products::findOrFail($id);
            $product->update($data);
            DB::commit();

            return redirect('/master-product')->with('success', 'Data berhasil diperbarui!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
    public function destroy($id)
    {
        $product = tb_products::find($id);

        if (!$product) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        try {
            $product->delete();
            return response()->json(['message' => 'Produk berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        $product = tb_products::find($id);
        if (!$product) {
            abort(404, "Product not found");
        }
        return response()->json($product);
    }

}