<?php

namespace App\Http\Controllers;

use App\Models\tb_outgoing_goods;
use App\Models\tb_sell;
use App\Models\tb_stores;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class DailySalesReportController extends Controller
{
    public function index(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $stores       = $isSuperadmin
            ? tb_stores::select('id', 'store_name')->orderBy('store_name')->get()
            : collect();

        $selectedStoreId = $isSuperadmin
            ? $request->query('store')
            : ($user?->store_id);

        $currentStoreName = $selectedStoreId
            ? tb_stores::where('id', $selectedStoreId)->value('store_name')
            : null;

        $today             = now('Asia/Jakarta')->toDateString();
        $defaultDateFrom   = $today;
        $defaultDateTo     = $today;
        $cashiers          = $this->availableCashiers($selectedStoreId, $today, $today);
        // Sembunyikan total penjualan untuk role staff/kasir
        $isCashierRole     = in_array(strtolower((string)($user?->roles)), ['kasir','cashier','staff']);

        return view('pages.admin.report.sales-today', [
            'stores'           => $stores,
            'isSuperadmin'     => $isSuperadmin,
            'selectedStoreId'  => $selectedStoreId,
            'currentStoreName' => $currentStoreName,
            'defaultDateFrom'  => $defaultDateFrom,
            'defaultDateTo'    => $defaultDateTo,
            'cashiers'         => $cashiers,
            'hideSalesTotal'   => $isCashierRole,
        ]);
    }

    public function data(Request $request)
    {
        $user         = $request->user();
        $isSuperadmin = $user?->roles === 'superadmin';
        $storeId      = $isSuperadmin ? $request->get('store') : ($user?->store_id);
        $storeId      = $storeId === '' ? null : $storeId;

        [$startDate, $endDate] = $this->resolveDateRange($request->get('date_from'), $request->get('date_to'));
        $cashier      = $request->get('cashier');

        $baseQuery = tb_outgoing_goods::query()
            ->join('tb_sells as s', 's.id', '=', 'tb_outgoing_goods.sell_id')
            ->leftJoin('tb_products as p', 'p.id', '=', 'tb_outgoing_goods.product_id')
            ->leftJoin('tb_stores as st', 'st.id', '=', 's.store_id')
            ->leftJoin('tb_customers as c', 'c.id', '=', 's.customer_id')
            ->when($storeId, fn ($q) => $q->where('s.store_id', $storeId))
            // abaikan penyesuaian stock opname (invoice dibuat otomatis)
            ->where(function ($q) {
                $q->whereNull('s.no_invoice')
                  ->orWhere('s.no_invoice', 'not like', 'SO-ADJ-%');
            })
            // abaikan pencatatan khusus stock opname
            ->when(Schema::hasColumn('tb_outgoing_goods','recorded_by'),
                fn($q) => $q->whereRaw('LOWER(COALESCE(TRIM(tb_outgoing_goods.recorded_by), "")) != ?', ['stock opname'])
            )
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween(
                    DB::raw('COALESCE(tb_outgoing_goods.date, s.date, tb_outgoing_goods.created_at, s.created_at)'),
                    [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]
                );
            })
            ->when(
                $cashier && Schema::hasColumn('tb_outgoing_goods', 'recorded_by'),
                fn ($q) => $q->where('tb_outgoing_goods.recorded_by', $cashier)
            )
            ->selectRaw('
                p.id as product_id,
                p.product_name,
                st.store_name,
                s.store_id,
                tb_outgoing_goods.recorded_by,
                DATE(COALESCE(tb_outgoing_goods.date, s.date, tb_outgoing_goods.created_at, s.created_at)) as activity_date,
                SUM(tb_outgoing_goods.quantity_out) as quantity_out,
                SUM(tb_outgoing_goods.discount) as discount,
                COALESCE(p.selling_price, 0) as unit_price,
                SUM(COALESCE(tb_outgoing_goods.quantity_out,0) * COALESCE(p.selling_price,0) - COALESCE(tb_outgoing_goods.discount,0)) as line_total,
                GROUP_CONCAT(DISTINCT s.id ORDER BY s.id DESC) as sell_ids,
                GROUP_CONCAT(DISTINCT s.no_invoice ORDER BY s.no_invoice DESC) as invoices
            ')
            ->groupBy(
                'p.id',
                'p.product_name',
                'st.store_name',
                's.store_id',
                'tb_outgoing_goods.recorded_by',
                DB::raw('DATE(COALESCE(tb_outgoing_goods.date, s.date, tb_outgoing_goods.created_at, s.created_at))'),
                'p.selling_price'
            );

        $summaryRows = (clone $baseQuery)->get();
        $totals = [
            'items'    => $summaryRows->count(),
            'quantity' => (float)$summaryRows->sum('quantity_out'),
            'sales'    => (float)$summaryRows->sum('line_total'),
            'discount' => (float)$summaryRows->sum('discount'),
        ];

        $cashiers = $this->availableCashiers($storeId, $startDate->toDateString(), $endDate->toDateString());

        // order by grouped/selected columns only to satisfy ONLY_FULL_GROUP_BY
        $dataQuery = (clone $baseQuery)
            ->orderByDesc('activity_date')
            ->orderBy('tb_outgoing_goods.recorded_by')
            ->orderBy('p.product_name');

        return DataTables::eloquent($dataQuery)
            ->addIndexColumn()
            ->editColumn('store_name', fn ($row) => $row->store_name ?? '-')
            ->addColumn('action', function ($row) {
                $ids = array_filter(array_map('trim', explode(',', $row->sell_ids ?? ''))); // buang kosong
                $invoices = array_map('trim', explode(',', $row->invoices ?? ''));
                if (empty($ids)) {
                    return '<span class="text-muted">-</span>';
                }
                $buttons = '';
                foreach ($ids as $idx => $sid) {
                    if (!$sid) continue;
                    $inv = $invoices[$idx] ?? ('INV-'.$sid);
                    $url = route('sell.detail', $sid);
                    $buttons .= '<a href="'.e($url).'" class="badge bg-primary me-1" target="_blank">'.e($inv).'</a>';
                }
                return '<div class="d-flex flex-wrap gap-1">'.($buttons ?: '<span class="text-muted">-</span>').'</div>';
            })
            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');
                if ($search) {
                    $like = '%' . $search . '%';
                    $query->where(function ($q) use ($like) {
                        $q->where('s.no_invoice', 'like', $like)
                          ->orWhere('st.store_name', 'like', $like)
                          ->orWhere('tb_outgoing_goods.recorded_by', 'like', $like)
                          ->orWhere('p.product_name', 'like', $like);
                    });
                }
            })
            ->rawColumns(['action'])
            ->with([
                'totals'      => $totals,
                'cashiers'    => $cashiers,
                'date_range'  => [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ],
            ])
            ->toJson();
    }

    private function resolveDateRange(?string $from, ?string $to): array
    {
        $tz   = 'Asia/Jakarta';
        $now  = now($tz);

        $start = $this->tryParseDate($from, $tz)?->startOfDay();
        $end   = $this->tryParseDate($to, $tz)?->endOfDay();

        if (!$start && !$end) {
            $start = $now->copy()->startOfDay();
            $end   = $now->copy()->endOfDay();
        } elseif ($start && !$end) {
            $end = $start->copy()->endOfDay();
        } elseif (!$start && $end) {
            $start = $end->copy()->startOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function tryParseDate(?string $value, string $tz): ?Carbon
    {
        if (!$value) return null;
        try {
            return Carbon::createFromFormat('Y-m-d', $value, $tz);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function availableCashiers(?int $storeId, string $startDate, string $endDate): array
    {
        if (!Schema::hasColumn('tb_outgoing_goods', 'recorded_by')) {
            return [];
        }

        $start = Carbon::createFromFormat('Y-m-d', $startDate, 'Asia/Jakarta')->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $endDate, 'Asia/Jakarta')->endOfDay();

        return tb_outgoing_goods::query()
            ->join('tb_sells as s', 's.id', '=', 'tb_outgoing_goods.sell_id')
            ->select('tb_outgoing_goods.recorded_by')
            ->when($storeId, fn ($q) => $q->where('s.store_id', $storeId))
            ->whereRaw('LOWER(COALESCE(TRIM(tb_outgoing_goods.recorded_by), "")) != ?', ['stock opname'])
            ->whereNotNull('recorded_by')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween(
                    DB::raw('COALESCE(tb_outgoing_goods.date, s.date, tb_outgoing_goods.created_at, s.created_at)'),
                    [$start, $end]
                );
            })
            ->groupBy('recorded_by')
            ->orderBy('recorded_by')
            ->pluck('recorded_by')
            ->filter()
            ->values()
            ->all();
    }
}
