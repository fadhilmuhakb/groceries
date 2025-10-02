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

        $user = Auth::user();
        $isSuperadmin = $user && $user->roles === 'superadmin';
        $storeId = $isSuperadmin ? $selectedStoreId : $user->store_id;

        $stores = $isSuperadmin ? DB::table('tb_stores')->get() : collect();

        switch ($range) {
            case 'daily':
                $labels = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i)->format('Y-m-d'));
                $groupBySales = DB::raw("DATE(date) as group_val");
                $groupByPurchases = DB::raw("DATE(created_at) as group_val");
                break;

            case 'weekly':
                $labels = collect(['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4']);
                break;

            case 'yearly':
                $startYear = now()->year - 4;
                $labels = collect(range($startYear, now()->year))->map(fn($y) => (string) $y);
                $groupBySales = DB::raw("YEAR(date) as group_val");
                $groupByPurchases = DB::raw("YEAR(created_at) as group_val");
                break;

            case 'monthly':
            default:
                $labels = collect(range(5, 0))->map(fn($i) => now()->subMonths($i)->format('Y-m'));
                $groupBySales = DB::raw("DATE_FORMAT(date, '%Y-%m') as group_val");
                $groupByPurchases = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as group_val");
                break;
        }

        $salesQuery = DB::table('tb_sells');
$purchaseQuery = DB::table('tb_purchases')
    ->where(function ($q) {
        $q->where('supplier_id', '!=', 1)
          ->orWhereNull('supplier_id');
    });


        if ($storeId) {
            $salesQuery->where('store_id', $storeId);
            $purchaseQuery->where('store_id', $storeId);
        }

        if ($range === 'weekly') {
            $salesRaw = $salesQuery 
                ->whereBetween('date', [now()->subDays(27)->startOfDay(), now()->endOfDay()])
                ->get();

            $purchaseRaw = $purchaseQuery
                ->whereBetween('created_at', [now()->subDays(27)->startOfDay(), now()->endOfDay()])
                ->get();

            $sales = collect([0, 0, 0, 0]);
            $hpp = collect([0, 0, 0, 0]);

            foreach ($salesRaw as $row) {
                $diff = now()->diffInDays(Carbon::parse($row->date));
                $index = intdiv($diff, 7);
                if ($index < 4) {
                    $sales[3 - $index] += $row->total_price;
                }
            }

            foreach ($purchaseRaw as $row) {
                $diff = now()->diffInDays(Carbon::parse($row->created_at));
                $index = intdiv($diff, 7);
                if ($index < 4) {
                    $hpp[3 - $index] += $row->total_price;
                }
            }
        } else {
            $salesData = $salesQuery
                ->select($groupBySales, DB::raw('SUM(total_price) as total'))
                ->groupBy('group_val')
                ->pluck('total', 'group_val');

            $purchaseData = $purchaseQuery
                ->select($groupByPurchases, DB::raw('SUM(total_price) as total'))
                ->groupBy('group_val')
                ->pluck('total', 'group_val');

            $sales = $labels->map(fn($label) => (float) ($salesData[$label] ?? 0));
            $hpp = $labels->map(fn($label) => (float) ($purchaseData[$label] ?? 0));
        }

        $laba = $sales->map(fn($val, $key) => $val - $hpp[$key]);

        $totalOmset = $sales->sum();
        $totalHpp = $hpp->sum();
        $totalLaba = $laba->sum();

        $topProductsQuery = DB::table('tb_outgoing_goods')
        ->join('tb_products', 'tb_outgoing_goods.product_id', '=', 'tb_products.id')
        ->join('tb_sells', 'tb_outgoing_goods.sell_id', '=', 'tb_sells.id')
        ->select('tb_products.product_name', DB::raw('SUM(tb_outgoing_goods.quantity_out) as total_sold'))
        ->groupBy('tb_products.product_name')
        ->orderByDesc('total_sold')
        ->limit(5);
    
    if ($storeId) {
        $topProductsQuery->where('tb_sells.store_id', $storeId);
    }
    
    $topProducts = $topProductsQuery->get();
    

        $topProducts = $topProductsQuery->get();

        return view('home', [
            'stores' => $stores,
            'selectedStoreId' => $selectedStoreId,
            'range' => $range,
            'labels' => $labels->values(),
            'omsetData' => $sales->values(),
            'hppData' => $hpp->values(),
            'labaData' => $laba->values(),
            'totalOmset' => $totalOmset,
            'totalHpp' => $totalHpp,
            'totalLaba' => $totalLaba,
            'topProducts' => $topProducts,
        ]);
    }
}
