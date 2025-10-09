<?php

namespace App\Http\Controllers;

use App\Models\tb_daily_revenues;
use App\Models\tb_outgoing_goods;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperadmin = $user?->roles === 'superadmin';

        // Ambil daftar toko untuk superadmin
        $stores = collect();
        $selectedStoreId = $request->query('store');

        if ($isSuperadmin) {
            // sesuaikan model & kolom: tb_stores(id, store_name)
            $stores = \App\Models\tb_stores::select('id','store_name')->orderBy('store_name')->get();
        } else {
            // non-superadmin: pakai toko user
            $selectedStoreId = $user?->store_id;
        }

        // untuk badge tampilan
        $currentStoreName = null;
        if ($selectedStoreId) {
            $currentStoreName = \App\Models\tb_stores::where('id',$selectedStoreId)->value('store_name');
        }

        return view('pages.admin.report.index', [
            'stores'          => $stores,
            'selectedStoreId' => $selectedStoreId,
            'currentStoreName'=> $currentStoreName,
        ]);
    }

  public function indexData(Request $request)
{
    $user = auth()->user();
    $isSuperadmin = $user?->roles === 'superadmin';
    $storeId = $request->get('store');

    if ($isSuperadmin && empty($storeId)) {
        return DataTables::of(collect())->toJson();
    } elseif (! $isSuperadmin) {
        $storeId = $user?->store_id;
    }

    // SELECT list dinamis (jangan paksa store_id kalau tidak ada)
    $select = ['id', 'user_id', 'amount', 'date'];
    if (schemaHasColumn('tb_daily_revenues', 'store_id')) {
        $select[] = 'store_id';
    }

    $query = tb_daily_revenues::with('user:id,name,store_id')
        ->select($select);

    // Filter by store dengan cek kolom
    if (schemaHasColumn('tb_daily_revenues','store_id')) {
        $query->where('store_id', $storeId);
    } else {
        $query->whereHas('user', fn($q) => $q->where('store_id', $storeId));
    }

    return DataTables::eloquent($query)
        ->addIndexColumn()
        ->addColumn('name', fn ($row) => optional($row->user)->name ?? '-')
        ->editColumn('amount', fn ($row) => (int) $row->amount)
        ->editColumn('date', fn ($row) => $row->date instanceof \Carbon\Carbon ? $row->date->toDateString() : $row->date)
        ->addColumn('status', function ($row) use ($storeId) {
            $filterDate  = \Carbon\Carbon::parse($row->date)->toDateString();
            $cashierName = trim(optional($row->user)->name ?? '');

            $soldTotal = tb_outgoing_goods::query()
                ->leftJoin('tb_products as p', 'p.id', '=', 'tb_outgoing_goods.product_id')
                ->when(schemaHasColumn('tb_outgoing_goods','store_id'), fn($q) => $q->where('tb_outgoing_goods.store_id', $storeId))
                ->where(function ($q) use ($filterDate) {
                    $q->whereDate('tb_outgoing_goods.date', $filterDate)
                      ->orWhereDate('tb_outgoing_goods.created_at', $filterDate);
                })
                ->when($cashierName !== '', function ($q) use ($cashierName) {
                    $normalized = strtolower(trim($cashierName));
                    $sql = "
                        REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(COALESCE(tb_outgoing_goods.recorded_by,''))), ' ', ''), '.', ''), '-', ''), '_', '')
                        = REPLACE(REPLACE(REPLACE(REPLACE(LOWER(?), ' ', ''), '.', ''), '-', ''), '_', '')
                    ";
                    $q->whereRaw($sql, [$normalized]);
                })
                ->selectRaw("
                    COALESCE(SUM(GREATEST(0,
                        (COALESCE(p.selling_price,0) * COALESCE(tb_outgoing_goods.quantity_out,0))
                        - COALESCE(tb_outgoing_goods.discount,0)
                    )), 0) as total
                ")
                ->value('total');

            return (int) $row->amount - (int) $soldTotal;
        })
        ->addColumn('action', fn ($row) =>
            '<div class="d-flex justify-content-center">
                <a href="'.route('report.detail', $row->id).'" class="btn btn-sm btn-success me-1">
                    Detail Penjualan <i class="bx bx-right-arrow-alt"></i>
                </a>
            </div>'
        )
        ->rawColumns(['action'])
        ->toJson();
}


  public function detail(Request $request, $id)
{
    // SELECT list dinamis
    $select = ['id','user_id','amount','date'];
    if (schemaHasColumn('tb_daily_revenues','store_id')) {
        $select[] = 'store_id';
    }

    $revenue = tb_daily_revenues::with('user:id,name')
        ->select($select)
        ->findOrFail($id);

    return view('pages.admin.report.detail', [
        'revenue' => $revenue,
        'cashier' => optional($revenue->user)->name ?? '-',
    ]);
}

public function detailData(Request $request, $id)
{
    $select = ['id','user_id','amount','date'];
    if (schemaHasColumn('tb_daily_revenues','store_id')) {
        $select[] = 'store_id';
    }

    $revenue = tb_daily_revenues::with('user:id,name,store_id')
        ->select($select)
        ->findOrFail($id);

    $filterDate  = \Carbon\Carbon::parse($revenue->date)->toDateString();
    $cashierName = trim(optional($revenue->user)->name ?? '');
    $storeId     = schemaHasColumn('tb_daily_revenues','store_id') ? $revenue->store_id : null;

    $query = tb_outgoing_goods::query()
        ->select('id','uuid','product_id','sell_id','date','quantity_out','discount','recorded_by','description','created_at')
        ->whereDate('date', $filterDate)
        ->when($cashierName !== '', fn($q) => $q->whereRaw('LOWER(TRIM(recorded_by)) = ?', [strtolower($cashierName)]))
        ->when(schemaHasColumn('tb_outgoing_goods','store_id') && $storeId, fn($q) => $q->where('store_id', $storeId));

    if (method_exists(\App\Models\tb_outgoing_goods::class, 'product')) {
        $query->with('product:id,product_name as name,selling_price as price');
    }

    return DataTables::eloquent($query)
        ->addIndexColumn()
        ->addColumn('product_name', fn($row) => optional($row->product)->name ?? $row->product_id)
        ->addColumn('price', fn($row) => (float) (optional($row->product)->price ?? 0))
        ->addColumn('subtotal', function ($row) {
            $price = (float) (optional($row->product)->price ?? 0);
            $qty   = (int)   ($row->quantity_out ?? 0);
            $disc  = (float) ($row->discount ?? 0);
            return max(0, ($price * $qty) - $disc);
        })
        ->toJson();
}
}

/**
 * Helper kecil untuk cek kolom ada (hindari error kalau struktur beda)
 */
if (! function_exists('schemaHasColumn')) {
    function schemaHasColumn(string $table, string $column): bool {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
