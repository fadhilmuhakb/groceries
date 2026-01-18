<?php

namespace App\Http\Controllers;

use App\Models\tb_daily_revenues;
use App\Models\tb_outgoing_goods;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperadmin = strtolower((string) ($user?->roles)) === 'superadmin';

        $stores = store_access_can_select($user)
            ? store_access_list($user)
            : collect();
        $selectedStoreId = store_access_resolve_id($request, $user, ['store']);

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
    $user         = auth()->user();
    $isSuperadmin = strtolower((string) ($user?->roles)) === 'superadmin';
    $storeId      = store_access_resolve_id($request, $user, ['store']);

    if ($isSuperadmin && empty($storeId)) {
        return \Yajra\DataTables\Facades\DataTables::of(collect())
            ->with(['totals' => ['amount' => 0, 'status' => 0]])
            ->toJson();
    }
    if (!$storeId) {
        return \Yajra\DataTables\Facades\DataTables::of(collect())
            ->with(['totals' => ['amount' => 0, 'status' => 0]])
            ->toJson();
    }

    // --- Range tanggal (default: hari ini) ---
    $dateFrom = $request->query('date_from');
    $dateTo   = $request->query('date_to');

    if (!$dateFrom && !$dateTo) {
        $start = \Carbon\Carbon::today('Asia/Jakarta')->startOfDay();
        $end   = \Carbon\Carbon::today('Asia/Jakarta')->endOfDay();
    } else {
        $start = \Carbon\Carbon::parse($dateFrom ?: $dateTo, 'Asia/Jakarta')->startOfDay();
        $end   = \Carbon\Carbon::parse($dateTo   ?: $dateFrom, 'Asia/Jakarta')->endOfDay();
        if ($start->gt($end)) { [$start, $end] = [$end, $start]; }
    }
    $startStr = $start->toDateString(); // YYYY-MM-DD
    $endStr   = $end->toDateString();

    // --- Select & base query ---
    $select = ['id', 'user_id', 'amount', 'date', 'created_at'];
    if (\Illuminate\Support\Facades\Schema::hasColumn('tb_daily_revenues', 'store_id')) {
        $select[] = 'store_id';
    }

    $base = \App\Models\tb_daily_revenues::with('user:id,name,store_id')
        ->select($select);

    // Filter store
    if (\Illuminate\Support\Facades\Schema::hasColumn('tb_daily_revenues','store_id')) {
        $base->where('store_id', $storeId);
    } else {
        $base->whereHas('user', fn($q) => $q->where('store_id', $storeId));
    }

    // 🔧 PENTING: jika kolom `date` adalah DATE, pakai whereDate
    $base->whereDate('date', '>=', $startStr)
         ->whereDate('date', '<=', $endStr);

    // Totals.amount
    $totalAmount = (int) (clone $base)->sum('amount');

    $hasSellerIdColumn = schemaHasColumn('tb_sells', 'seller_id');
    $hasInvoiceColumn  = schemaHasColumn('tb_sells', 'no_invoice');

    $excludeStockOpname = function ($query) use ($hasSellerIdColumn, $hasInvoiceColumn) {
        if ($hasSellerIdColumn) {
            $query->where('s.seller_id', '!=', 1);
            return;
        }

        if ($hasInvoiceColumn) {
            $query->where(function ($q) {
                $q->whereNull('s.no_invoice')
                  ->orWhere('s.no_invoice', 'not like', 'SO-ADJ-%');
            });
        }
    };

    // Ambil transaksi per nota (pakai total_price agar konsisten dengan halaman home)
    $salesRawQuery = \Illuminate\Support\Facades\DB::table('tb_sells as s')
        ->join('tb_outgoing_goods as og', 'og.sell_id', '=', 's.id')
        ->when(
            schemaHasColumn('tb_outgoing_goods', 'deleted_at'),
            fn ($q) => $q->whereNull('og.deleted_at')
        )
        ->when($storeId, fn ($q) => $q->where('s.store_id', $storeId))
        ->whereBetween('s.date', [$startStr, $endStr])
        ->selectRaw('s.id, s.date, s.total_price, s.created_at, MAX(og.created_at) as og_created_at, MAX(og.recorded_by) as recorded_by')
        ->groupBy('s.id', 's.date', 's.total_price', 's.created_at');

    $excludeStockOpname($salesRawQuery);
    $salesRaw = $salesRawQuery->get();

    // Kelompokkan transaksi per kasir+tanggal, urut created_at
    $salesBuckets = [];
    foreach ($salesRaw as $sale) {
        $dateKey = $sale->date
            ? \Carbon\Carbon::parse($sale->date)->toDateString()
            : \Carbon\Carbon::parse($sale->created_at)->toDateString();
        $norm  = $this->normalizeName($sale->recorded_by);
        $total = (float) $sale->total_price;
        $createdAt = $sale->og_created_at
            ? \Carbon\Carbon::parse($sale->og_created_at)
            : ($sale->created_at ? \Carbon\Carbon::parse($sale->created_at) : \Carbon\Carbon::parse($dateKey.' 23:59:59'));
        $salesBuckets[$dateKey.'|'.$norm][] = [
            'created_at' => $createdAt,
            'total'      => (int) $total,
        ];
    }

    foreach ($salesBuckets as &$entries) {
        usort($entries, fn ($a, $b) => $a['created_at']->getTimestamp() <=> $b['created_at']->getTimestamp());
    }
    unset($entries);

    // State per bucket agar omset per sesi tidak dobel saat kasir logout lebih dari 1x per hari
    $omsetPerRevenue = [];
    $bucketState     = []; // key => ['cursor' => int, 'sum' => int]

    $consumeOmset = function (string $key, \Carbon\Carbon $closeAt) use (&$bucketState, $salesBuckets) {
        if (!isset($bucketState[$key])) {
            $bucketState[$key] = ['cursor' => 0, 'sum' => 0];
        }

        $state   = $bucketState[$key];
        $entries = $salesBuckets[$key] ?? [];
        $prevSum = $state['sum'];

        while ($state['cursor'] < count($entries) && $entries[$state['cursor']]['created_at']->lte($closeAt)) {
            $state['sum'] += $entries[$state['cursor']]['total'];
            $state['cursor']++;
        }

        $bucketState[$key] = $state;
        return $state['sum'] - $prevSum;
    };

    // Totals.status + omset per row (urut tanggal & waktu input)
    $rowsForTotals = (clone $base)->orderBy('date')->orderBy('created_at')->get(['id','user_id','amount','date','created_at']);
    $totalStatus   = 0;
    $totalOmset    = 0;

    foreach ($rowsForTotals as $r) {
        $dateKey = $r->date instanceof \Carbon\Carbon
            ? $r->date->toDateString()
            : \Carbon\Carbon::parse($r->date)->toDateString();
        $cashierName = trim(optional($r->user)->name ?? '');
        $norm    = $this->normalizeName($cashierName);
        $closeAt = $r->created_at
            ? \Carbon\Carbon::parse($r->created_at)
            : \Carbon\Carbon::parse($dateKey.' 23:59:59');

        $sessionOmset            = $consumeOmset($dateKey.'|'.$norm, $closeAt);
        $omsetPerRevenue[$r->id] = $sessionOmset;
        $totalOmset             += $sessionOmset;
        $totalStatus            += ((int)$r->amount - $sessionOmset);
    }

    return \Yajra\DataTables\Facades\DataTables::eloquent($base)
        ->addIndexColumn()
        ->addColumn('name', fn ($row) => optional($row->user)->name ?? '-')
        ->editColumn('amount', fn ($row) => (int) $row->amount)
        ->addColumn('omset', fn ($row) => (int) ($omsetPerRevenue[$row->id] ?? 0))
        ->editColumn('date', fn ($row) =>
            $row->date instanceof \Carbon\Carbon
                ? $row->date->toDateString()
                : (\Carbon\Carbon::parse($row->date)->toDateString())
        )
        ->addColumn('status', fn ($row) => (int) $row->amount - (int) ($omsetPerRevenue[$row->id] ?? 0))
        ->addColumn('action', fn ($row) =>
            '<div class="d-flex justify-content-center">
                <a href="'.route('report.detail', $row->id).'" class="btn btn-sm btn-success me-1">
                    Detail Penjualan <i class="bx bx-right-arrow-alt"></i>
                </a>
            </div>'
        )
        ->rawColumns(['action'])
        ->with(['totals' => [
            'amount' => $totalAmount,
            'omset'  => $totalOmset,
            'status' => $totalStatus,
        ]])
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
private function normalizeName(?string $name): string
{
    $n = mb_strtolower(trim((string) $name), 'UTF-8');
    return str_replace([' ', '.', '-', '_'], '', $n);
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
