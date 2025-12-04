<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\tb_master_menus;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.sidebar', function ($view) {
            try {
                $user = Auth::user();
                $role = $user ? strtolower(trim($user->roles)) : '';
                if ($role === '') {
                    $view->with('sidebarMenus', collect());
                    return;
                }

                $menus = tb_master_menus::treeAllowedForRoleName($role);
                Log::info('SIDEBAR', ['role' => $role, 'menu_count' => $menus->count()]);
                $view->with('sidebarMenus', $menus);
            } catch (\Throwable $e) {
                Log::error('SIDEBAR_ERROR', ['msg' => $e->getMessage()]);
                $view->with('sidebarMenus', collect());
            }
        });

        View::composer('layouts.header', function ($view) {
            try {
                $user = Auth::user();
                if (!$user) {
                    $view->with('lowStockItemsGlobal', collect());
                    return;
                }
                $storeId = $user->roles === 'superadmin' ? null : ($user->store_id ?? null);
                $items = $storeId
                    ? $this->lowStockItems((int)$storeId)
                    : $this->lowStockAllStores();
                $view->with('lowStockItemsGlobal', $items);
            } catch (\Throwable $e) {
                Log::error('LOW_STOCK_HEADER', ['msg' => $e->getMessage()]);
                $view->with('lowStockItemsGlobal', collect());
            }
        });
    }

    private function lowStockItems(int $storeId)
    {
        $incomingSub = DB::table('tb_incoming_goods as ig')
            ->when(
                Schema::hasColumn('tb_incoming_goods', 'store_id'),
                fn ($q) => $q->where('ig.store_id', $storeId)
                             ->when(Schema::hasColumn('tb_incoming_goods', 'is_pending_stock'),
                                 fn ($q2) => $q2->where('ig.is_pending_stock', false)),
                fn ($q) => $q->join('tb_purchases as pur', 'ig.purchase_id', '=', 'pur.id')
                             ->where('pur.store_id', $storeId)
                             ->when(Schema::hasColumn('tb_incoming_goods', 'is_pending_stock'),
                                 fn ($q2) => $q2->where('ig.is_pending_stock', false))
            )
            ->select('ig.product_id', DB::raw('SUM(ig.stock) AS total_in'))
            ->groupBy('ig.product_id');

        $outgoingSub = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as sl', 'og.sell_id', '=', 'sl.id')
            ->where('sl.store_id', $storeId)
            ->when(Schema::hasColumn('tb_outgoing_goods', 'is_pending_stock'),
                fn ($q) => $q->where('og.is_pending_stock', false))
            ->select('og.product_id', DB::raw('SUM(og.quantity_out) AS total_out'))
            ->groupBy('og.product_id');

        return DB::table('tb_products as p')
            ->join('tb_product_store_thresholds as sp', function ($join) use ($storeId) {
                $join->on('sp.product_id', '=', 'p.id')
                     ->where('sp.store_id', '=', $storeId);
            })
            ->leftJoinSub($incomingSub, 'incoming', fn ($join) => $join->on('incoming.product_id', '=', 'p.id'))
            ->leftJoinSub($outgoingSub, 'outgoing', fn ($join) => $join->on('outgoing.product_id', '=', 'p.id'))
            ->select(
                'p.id',
                'p.product_code',
                'p.product_name',
                'sp.store_id',
                'sp.min_stock',
                'sp.max_stock',
                DB::raw('(COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)) as stock_system'),
                DB::raw('GREATEST(COALESCE(sp.max_stock,0) - (COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)),0) as po_qty')
            )
            ->whereNotNull('sp.min_stock')
            ->whereRaw('COALESCE(sp.min_stock,0) > 0')
            ->whereRaw('(COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)) <= COALESCE(sp.min_stock, 0)')
            ->orderBy('p.product_name')
            ->limit(50)
            ->get();
    }

    private function lowStockAllStores()
    {
        $incomingSub = DB::table('tb_incoming_goods as ig')
            ->join('tb_purchases as pur', 'ig.purchase_id', '=', 'pur.id')
            ->select('pur.store_id', 'ig.product_id', DB::raw('SUM(ig.stock) AS total_in'))
            ->groupBy('pur.store_id', 'ig.product_id');

        $outgoingSub = DB::table('tb_outgoing_goods as og')
            ->join('tb_sells as sl', 'og.sell_id', '=', 'sl.id')
            ->select('sl.store_id', 'og.product_id', DB::raw('SUM(og.quantity_out) AS total_out'))
            ->groupBy('sl.store_id', 'og.product_id');

        return DB::table('tb_products as p')
            ->join('tb_product_store_thresholds as sp', 'sp.product_id', '=', 'p.id')
            ->join('tb_stores as st', 'st.id', '=', 'sp.store_id')
            ->leftJoinSub($incomingSub, 'incoming', function ($join) {
                $join->on('incoming.product_id', '=', 'p.id')
                     ->on('incoming.store_id', '=', 'sp.store_id');
            })
            ->leftJoinSub($outgoingSub, 'outgoing', function ($join) {
                $join->on('outgoing.product_id', '=', 'p.id')
                     ->on('outgoing.store_id', '=', 'sp.store_id');
            })
            ->select(
                'p.id',
                'p.product_code',
                'p.product_name',
                'sp.store_id',
                'st.store_name',
                'sp.min_stock',
                'sp.max_stock',
                DB::raw('(COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)) as stock_system'),
                DB::raw('GREATEST(COALESCE(sp.max_stock,0) - (COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)),0) as po_qty')
            )
            ->whereNotNull('sp.min_stock')
            ->whereRaw('COALESCE(sp.min_stock,0) > 0')
            ->whereRaw('(COALESCE(incoming.total_in, 0) - COALESCE(outgoing.total_out, 0)) <= COALESCE(sp.min_stock, 0)')
            ->orderBy('st.store_name')
            ->orderBy('p.product_name')
            ->limit(50)
            ->get();
    }
}
