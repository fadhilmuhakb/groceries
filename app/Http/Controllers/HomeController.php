<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 'monthly');
        $user = session('user_data') ?? [];
        $isSuperadmin = ($user['roles'] ?? null) === 'superadmin';
        $storeId = $user['store_id'] ?? null;
        $sales = collect();
        $expenses = collect();
        $labels = collect();

        if ($range === 'weekly') {
            $labels = collect(['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4']);

            $sales = collect([0, 0, 0, 0]);
            $expenses = collect([0, 0, 0, 0]);

            $salesQuery = DB::table('tb_sells');
            $purchaseQuery = DB::table('tb_purchases');

            if (!$isSuperadmin && $storeId > 0) {
                $salesQuery->where('store_id', $storeId);
                $purchaseQuery->where('store_id', $storeId);
            }

            foreach ($salesQuery->whereBetween('date', [now()->subDays(27), now()])->get() as $row) {
                $diff = now()->diffInDays(Carbon::parse($row->date));
                $index = floor($diff / 7);
                if ($index < 4) $sales[3 - $index] += $row->total_price;
            }

            foreach ($purchaseQuery->whereBetween('created_at', [now()->subDays(27), now()])->get() as $row) {
                $diff = now()->diffInDays(Carbon::parse($row->created_at));
                $index = floor($diff / 7);
                if ($index < 4) $expenses[3 - $index] += $row->total_price;
            }
        } else {
            // Handle daily, monthly, yearly
            switch ($range) {
                case 'daily':
                    $labels = collect(range(0, 6))->map(fn($i) => Carbon::today()->subDays($i)->format('Y-m-d'))->reverse();
                    $groupKeySales = DB::raw("DATE(date) as group_val");
                    $groupKeyPurchases = DB::raw("DATE(created_at) as group_val");
                    $groupBySales = DB::raw("DATE(date)");
                    $groupByPurchases = DB::raw("DATE(created_at)");
                    break;

                case 'yearly':
                    $labels = collect(range(now()->year - 4, now()->year))->map(fn($y) => (string) $y);
                    $groupKeySales = DB::raw("YEAR(date) as group_val");
                    $groupKeyPurchases = DB::raw("YEAR(created_at) as group_val");
                    $groupBySales = DB::raw("YEAR(date)");
                    $groupByPurchases = DB::raw("YEAR(created_at)");
                    break;

                case 'monthly':
                default:
                    $labels = collect(range(0, 5))->map(fn($i) => now()->subMonths($i)->format('m'))->reverse();
                    $groupKeySales = DB::raw("MONTH(date) as group_val");
                    $groupKeyPurchases = DB::raw("MONTH(created_at) as group_val");
                    $groupBySales = DB::raw("MONTH(date)");
                    $groupByPurchases = DB::raw("MONTH(created_at)");
                    break;
            }

            $salesQuery = DB::table('tb_sells');
            $purchaseQuery = DB::table('tb_purchases');

            if (!$isSuperadmin && $storeId > 0) {
                $salesQuery->where('store_id', $storeId);
                $purchaseQuery->where('store_id', $storeId);
            }

            $salesData = $salesQuery
                ->select($groupKeySales, DB::raw('SUM(total_price) as total'))
                ->groupBy($groupBySales)
                ->pluck('total', 'group_val');

            $purchaseData = $purchaseQuery
                ->select($groupKeyPurchases, DB::raw('SUM(total_price) as total'))
                ->groupBy($groupByPurchases)
                ->pluck('total', 'group_val');

            $sales = $labels->map(fn($label, $i) => (float) ($salesData[$this->getGroupKey($range, $label, $i)] ?? 0))->values();
            $expenses = $labels->map(fn($label, $i) => (float) ($purchaseData[$this->getGroupKey($range, $label, $i)] ?? 0))->values();
        }

        // Pie Chart per toko
        $storeSales = [];
        $storeExpenses = [];

        if ($isSuperadmin) {
            $stores = DB::table('tb_stores')->get();
            foreach ($stores as $store) {
                $storeSales[$store->store_name] = (float) DB::table('tb_sells')
                    ->where('store_id', $store->id)
                    ->sum('total_price');

                $storeExpenses[$store->store_name] = (float) DB::table('tb_purchases')
                    ->where('store_id', $store->id)
                    ->sum('total_price');
            }
        }

        return view('home', [
            'months' => $labels->values(),
            'sales' => $sales,
            'expenses' => $expenses,
            'storeSales' => $storeSales,
            'storeExpenses' => $storeExpenses,
        ]);
    }

    private function getGroupKey($range, $label, $index)
    {
        return match ($range) {
            'daily' => $label,
            'weekly' => $index,
            'monthly' => $index + 1,
            'yearly' => (int) $label,
            default => $index + 1,
        };
    }
}
