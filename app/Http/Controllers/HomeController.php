<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
public function index(Request $request)
{
    $range = $request->get('range', 'monthly');
    $selectedStoreId = $request->get('store', null);

    $dateFromReq = $request->get('date_from');
    $dateToReq   = $request->get('date_to');

    // normalize date range (optional -> nullable Carbon)
    $dateFrom = $dateFromReq ? Carbon::createFromFormat('Y-m-d', $dateFromReq)->startOfDay() : null;
    $dateTo   = $dateToReq   ? Carbon::createFromFormat('Y-m-d', $dateToReq)->endOfDay()   : null;

    // kalau hanya salah satu yang diisi
    if ($dateFrom && !$dateTo) {
        $dateTo = (clone $dateFrom)->endOfDay();
    } elseif (!$dateFrom && $dateTo) {
        $dateFrom = (clone $dateTo)->startOfDay();
    }

    $user = Auth::user();
    $isSuperadmin = $user && $user->roles === 'superadmin';
    $storeId = $isSuperadmin ? $selectedStoreId : $user->store_id;

    $stores = $isSuperadmin ? DB::table('tb_stores')->get() : collect();

    $usingSpecificRange = $dateFrom && $dateTo;

    // ===== Labels & Grouping =====
    if ($usingSpecificRange) {
        // label per hari dari dateFrom..dateTo
        $labels = collect();
        $cursor = (clone $dateFrom)->startOfDay();
        while ($cursor->lte($dateTo)) {
            $labels->push($cursor->format('Y-m-d'));
            $cursor->addDay();
        }
        // group by DATE untuk sejajarkan omzet & HPP
        $groupBySales = DB::raw("DATE(s.date) as group_val");
        $groupByHpp   = DB::raw("DATE(s.date) as group_val");
    } else {
        // pakai logic lama (daily/weekly/monthly/yearly)
        switch ($range) {
            case 'daily':
                $labels = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i)->format('Y-m-d'));
                $groupBySales = DB::raw("DATE(s.date) as group_val");
                $groupByHpp   = DB::raw("DATE(s.date) as group_val");
                break;

            case 'weekly':
                $labels = collect(['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4']);
                break;

            case 'yearly':
                $startYear = now()->year - 4;
                $labels = collect(range($startYear, now()->year))->map(fn($y) => (string) $y);
                $groupBySales = DB::raw("YEAR(s.date) as group_val");
                $groupByHpp   = DB::raw("YEAR(s.date) as group_val");
                break;

            case 'monthly':
            default:
                $labels = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
                $groupBySales = DB::raw("DATE_FORMAT(s.date, '%Y-%m') as group_val");
                $groupByHpp   = DB::raw("DATE_FORMAT(s.date, '%Y-%m') as group_val");
                break;
        }
    }

    // ===== Base queries =====
    $salesQuery = DB::table('tb_sells as s')->select();
    $hppBase = DB::table('tb_outgoing_goods as og')
        ->join('tb_sells as s', 'og.sell_id', '=', 's.id')
        ->join('tb_products as p', 'og.product_id', '=', 'p.id');

    if ($storeId) {
        $salesQuery->where('s.store_id', $storeId);
        $hppBase->where('s.store_id', $storeId);
    }

    // Terapkan filter tanggal spesifik bila ada
    if ($usingSpecificRange) {
        $salesQuery->whereBetween('s.date', [$dateFrom, $dateTo]);
        $hppBase->whereBetween('s.date', [$dateFrom, $dateTo]);
    }

    // ===== Perhitungan =====
    if (!$usingSpecificRange && $range === 'weekly') {
        // logic mingguan lama tetap
        $start = now()->subDays(27)->startOfDay();
        $end   = now()->endOfDay();

        $salesRaw = DB::table('tb_sells as s')
            ->when($storeId, fn($q) => $q->where('s.store_id', $storeId))
            ->whereBetween('s.date', [$start, $end])
            ->get(['s.date', 's.total_price']);

        $hppRaw = $hppBase
            ->whereBetween('s.date', [$start, $end])
            ->get(['s.date', DB::raw('og.quantity_out as qty'), DB::raw('p.purchase_price as cogs_unit')]);

        $sales = collect([0,0,0,0]);
        $hpp   = collect([0,0,0,0]);

        foreach ($salesRaw as $row) {
            $diff  = now()->diffInDays(Carbon::parse($row->date));
            $index = intdiv($diff, 7);
            if ($index < 4) $sales[3 - $index] += (float)$row->total_price;
        }

        foreach ($hppRaw as $row) {
            $diff  = now()->diffInDays(Carbon::parse($row->date));
            $index = intdiv($diff, 7);
            if ($index < 4) $hpp[3 - $index] += (float)$row->qty * (float)$row->cogs_unit;
        }
    } else {
        // OMZET by buckets
        $salesData = DB::table('tb_sells as s')
            ->select($groupBySales, DB::raw('SUM(s.total_price) as total'))
            ->when($storeId, fn($q) => $q->where('s.store_id', $storeId))
            ->when($usingSpecificRange, fn($q) => $q->whereBetween('s.date', [$dateFrom, $dateTo]))
            ->groupBy('group_val')
            ->pluck('total', 'group_val');

        // HPP (COGS) by buckets
        $hppData = $hppBase
            ->select($groupByHpp, DB::raw('SUM(og.quantity_out * p.purchase_price) as total'))
            ->when($usingSpecificRange, fn($q) => $q->whereBetween('s.date', [$dateFrom, $dateTo]))
            ->groupBy('group_val')
            ->pluck('total', 'group_val');

        $sales = $labels->map(fn($label) => (float) ($salesData[$label] ?? 0));
        $hpp   = $labels->map(fn($label) => (float) ($hppData[$label] ?? 0));
    }

    $laba = $sales->map(fn($val, $i) => $val - ($hpp[$i] ?? 0));
    $totalOmset = $sales->sum();
    $totalHpp   = $hpp->sum();
    $totalLaba  = $laba->sum();

    // ===== Top 5 Produk (ikut filter tanggal & store) =====
    $topProductsQuery = DB::table('tb_outgoing_goods as og')
        ->join('tb_products as p', 'og.product_id', '=', 'p.id')
        ->join('tb_sells as s', 'og.sell_id', '=', 's.id')
        ->select('p.product_name', DB::raw('SUM(og.quantity_out) as total_sold'))
        ->groupBy('p.product_name')
        ->orderByDesc('total_sold')
        ->limit(5);

    if ($storeId) {
        $topProductsQuery->where('s.store_id', $storeId);
    }
    if ($usingSpecificRange) {
        $topProductsQuery->whereBetween('s.date', [$dateFrom, $dateTo]);
    }

    $topProducts = $topProductsQuery->get();

    return view('home', [
        'stores'          => $stores,
        'selectedStoreId' => $selectedStoreId,
        'range'           => $range,
        'labels'          => $labels->values(),
        'omsetData'       => $sales->values(),
        'hppData'         => $hpp->values(),
        'labaData'        => $laba->values(),
        'totalOmset'      => $totalOmset,
        'totalHpp'        => $totalHpp,
        'totalLaba'       => $totalLaba,
        'topProducts'     => $topProducts,
    ]);
}

}