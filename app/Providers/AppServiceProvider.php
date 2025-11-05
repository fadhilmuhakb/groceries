<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    }
}
