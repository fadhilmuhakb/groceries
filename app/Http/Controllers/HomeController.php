<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 'monthly');
        $user = Auth::user();
        $isSuperadmin = $user && $user->roles === 'superadmin';
        $storeId = $user->store_id ?? null;

        $sales = collect();
        $expenses = collect();
        $labels = collect();

        if ($range === 'weekly') {
            $labels = collect(['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4']);
            $sales = collect([0, 0, 0, 0]);
            $expenses = collect([0, 0, 0, 0]);

            $salesQuery = DB::table('tb_sells');
            $purchaseQuery = DB::table('tb_purchases');

            if (!$isSuperadmin && $storeId) {
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

            if (!$isSuperadmin && $storeId) {
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

        $storeSales = [];
        $storeExpenses = [];

        if ($isSuperadmin) {
            $stores = DB::table('tb_stores')->get();
            foreach ($stores as $store) {
                $storeSales[$store->store_name] = (float) DB::table('tb_sells')->where('store_id', $store->id)->sum('total_price');
                $storeExpenses[$store->store_name] = (float) DB::table('tb_purchases')->where('store_id', $store->id)->sum('total_price');
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

    public function exportPenjualan(Request $request)
    {
        $user = Auth::user();
        $isSuperadmin = $user && $user->roles === 'superadmin';
        $storeId = $user->store_id ?? null;

        $query = DB::table('tb_outgoing_goods')
            ->join('tb_products', 'tb_products.id', '=', 'tb_outgoing_goods.product_id')
            ->join('tb_sells', 'tb_sells.id', '=', 'tb_outgoing_goods.sell_id')
            ->leftJoin('tb_stores', 'tb_stores.id', '=', 'tb_sells.store_id')
            ->select([
                'tb_products.product_name',
                'tb_stores.store_name',
                'tb_sells.date',
                'tb_products.purchase_price',
                'tb_products.selling_price',
                'tb_outgoing_goods.quantity_out',
                DB::raw('tb_products.selling_price * tb_outgoing_goods.quantity_out as total_penjualan'),
            ]);

        if (!$isSuperadmin && $storeId) {
            $query->where('tb_sells.store_id', $storeId);
        }

        $data = $query->get()->map(function ($item) {
            return [
                'Produk' => $item->product_name,
                'Toko' => $item->store_name ?? '-',
                'Tanggal' => $item->date,
                'Harga Beli' => $item->purchase_price,
                'Harga Jual' => $item->selling_price,
                'Qty' => $item->quantity_out,
                'Total Penjualan' => $item->total_penjualan,
            ];
        });

        return Excel::download(new class($data) implements FromCollection, WithHeadings {
            protected $rows;
            public function __construct(Collection $rows) { $this->rows = $rows; }
            public function collection() { return $this->rows; }
            public function headings(): array {
                return ['Produk', 'Toko', 'Tanggal', 'Harga Beli', 'Harga Jual', 'Qty', 'Total Penjualan'];
            }
        }, 'penjualan-detail.xlsx');
    }
}
