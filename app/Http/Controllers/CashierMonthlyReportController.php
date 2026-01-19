<?php

namespace App\Http\Controllers;

use App\Models\tb_stores;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class CashierMonthlyReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canSelectStore = store_access_can_select($user);
        $stores = $canSelectStore ? store_access_list($user) : collect();
        $selectedStoreId = store_access_resolve_id($request, $user, ['store']);

        $currentStoreName = $selectedStoreId
            ? tb_stores::where('id', $selectedStoreId)->value('store_name')
            : null;

        $defaultTo = now('Asia/Jakarta')->startOfMonth();
        $defaultFrom = (clone $defaultTo);

        return view('pages.admin.report.cashier-monthly', [
            'stores'           => $stores,
            'isSuperadmin'     => $canSelectStore,
            'selectedStoreId'  => $selectedStoreId,
            'currentStoreName' => $currentStoreName,
            'defaultMonthFrom' => $defaultFrom->format('Y-m'),
            'defaultMonthTo'   => $defaultTo->format('Y-m'),
        ]);
    }

    public function data(Request $request)
    {
        $user = $request->user();
        $storeId = store_access_resolve_id($request, $user, ['store']);

        if (!Schema::hasColumn('tb_outgoing_goods', 'recorded_by')) {
            return DataTables::of(collect())
                ->with(['totals' => $this->emptyTotals($request)])
                ->toJson();
        }

        [$startMonth, $endMonth] = $this->resolveMonthRange(
            $request->get('month_from'),
            $request->get('month_to')
        );

        $startDate = $startMonth->copy()->startOfMonth();
        $endDate = $endMonth->copy()->endOfMonth();

        $hasSellerIdColumn = Schema::hasColumn('tb_sells', 'seller_id');
        $hasInvoiceColumn  = Schema::hasColumn('tb_sells', 'no_invoice');

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

        $dateExpr = 'COALESCE(og.date, s.date, og.created_at, s.created_at)';
        $monthExpr = "DATE_FORMAT($dateExpr, '%Y-%m')";
        $dayExpr = "DATE($dateExpr)";
        $cashierKeyExpr = "LOWER(TRIM(og.recorded_by))";

        $salesBase = DB::table('tb_sells as s')
            ->join('tb_outgoing_goods as og', 'og.sell_id', '=', 's.id')
            ->when(
                Schema::hasColumn('tb_outgoing_goods', 'deleted_at'),
                fn ($q) => $q->whereNull('og.deleted_at')
            )
            ->when($storeId, fn ($q) => $q->where('s.store_id', $storeId))
            ->whereBetween(DB::raw($dateExpr), [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereNotNull('og.recorded_by')
            ->whereRaw('COALESCE(TRIM(og.recorded_by), "") <> ""')
            ->whereRaw('LOWER(COALESCE(TRIM(og.recorded_by), "")) != ?', ['stock opname']);

        $excludeStockOpname($salesBase);

        $perSale = $salesBase
            ->selectRaw("
                s.id as sell_id,
                $monthExpr as sale_month,
                $dayExpr as sale_day,
                $cashierKeyExpr as cashier_key,
                MIN(TRIM(og.recorded_by)) as cashier_name,
                s.total_price as total_price
            ")
            ->groupBy(
                's.id',
                DB::raw($monthExpr),
                DB::raw($dayExpr),
                DB::raw($cashierKeyExpr),
                's.total_price'
            );

        $monthly = DB::query()
            ->fromSub($perSale, 'sales')
            ->selectRaw('
                sale_month,
                cashier_key,
                MIN(cashier_name) as cashier_name,
                SUM(total_price) as total_sales,
                COUNT(*) as transactions,
                COUNT(DISTINCT sale_day) as active_days
            ')
            ->groupBy('sale_month', 'cashier_key')
            ->orderBy('sale_month')
            ->orderBy('cashier_name');

        $rows = $monthly->get();
        $rowsWithMetrics = $this->applyMetrics($rows);
        $totals = $this->buildTotals($rowsWithMetrics, $startMonth, $endMonth);

        return DataTables::of($rowsWithMetrics)
            ->addIndexColumn()
            ->with(['totals' => $totals])
            ->toJson();
    }

    private function resolveMonthRange(?string $from, ?string $to): array
    {
        $tz = 'Asia/Jakarta';
        $now = now($tz)->startOfMonth();

        $start = $this->tryParseMonth($from, $tz);
        $end = $this->tryParseMonth($to, $tz);

        if (!$start && !$end) {
            $end = $now;
            $start = (clone $now);
        } elseif ($start && !$end) {
            $end = (clone $start);
        } elseif (!$start && $end) {
            $start = (clone $end);
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function tryParseMonth(?string $value, string $tz): ?Carbon
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m', $value, $tz)->startOfMonth();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function applyMetrics($rows)
    {
        $rows = collect($rows);
        $withMetrics = collect();

        $grouped = $rows->groupBy('cashier_key');
        foreach ($grouped as $items) {
            $sorted = $items->sortBy('sale_month')->values();
            $prevTotal = null;

            foreach ($sorted as $item) {
                $total = (float) $item->total_sales;
                $days = (int) $item->active_days;

                $item->avg_daily = $days > 0 ? ($total / $days) : 0;
                if ($prevTotal !== null && $prevTotal > 0) {
                    $item->mom_growth = (($total - $prevTotal) / $prevTotal) * 100;
                } else {
                    $item->mom_growth = null;
                }

                $prevTotal = $total;
                $withMetrics->push($item);
            }
        }

        return $withMetrics->values();
    }

    private function buildTotals($rows, Carbon $startMonth, Carbon $endMonth): array
    {
        $rows = collect($rows);

        $cashierCount = $rows->pluck('cashier_key')->unique()->count();
        $totalSales = (float) $rows->sum('total_sales');
        $monthsCount = $startMonth->diffInMonths($endMonth) + 1;

        $avgPerCashierMonth = ($cashierCount > 0 && $monthsCount > 0)
            ? ($totalSales / ($cashierCount * $monthsCount))
            : 0;

        return [
            'total_sales' => $totalSales,
            'avg_cashier_month' => $avgPerCashierMonth,
            'cashier_count' => $cashierCount,
            'months_count' => $monthsCount,
        ];
    }

    private function emptyTotals(Request $request): array
    {
        [$startMonth, $endMonth] = $this->resolveMonthRange(
            $request->get('month_from'),
            $request->get('month_to')
        );

        $monthsCount = $startMonth->diffInMonths($endMonth) + 1;

        return [
            'total_sales' => 0,
            'avg_cashier_month' => 0,
            'cashier_count' => 0,
            'months_count' => $monthsCount,
        ];
    }
}
