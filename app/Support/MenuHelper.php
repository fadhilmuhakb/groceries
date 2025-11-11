<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MenuHelper
{
    public static function firstAllowedRouteFor(?string $roleName = null): ?string
    {
        $role = $roleName ?: strtolower(trim((string) (Auth::user()->roles ?? '')));
        if ($role === '') return null;

        $q = DB::table('tb_master_menuses as m')
            ->join('tb_master_menu_roles as r', 'r.menu_id', '=', 'm.id')
            ->whereRaw('LOWER(TRIM(r.role_name)) = ?', [$role])
            ->where('m.is_active', 1)
            ->whereNotNull('m.menu_path')->where('m.menu_path', '<>', '');

        if (Schema::hasColumn('tb_master_menuses', 'sort')) {
            $q->orderBy('m.sort')->orderBy('m.id');
        } else {
            $q->orderBy('m.id');
        }

        foreach ($q->pluck('m.menu_path') as $routeName) {
            if ($routeName && Route::has($routeName)) {
                return $routeName; // nama route valid, mis. 'sales.index'
            }
        }

        return null;
    }

    public static function roleHasRoute(string $routeName, ?string $roleName = null): bool
    {
        $routeName = trim($routeName);
        if ($routeName === '') return false;

        $role = $roleName ?: strtolower(trim((string) (Auth::user()->roles ?? '')));
        if ($role === '') return false;

        return DB::table('tb_master_menuses as m')
            ->join('tb_master_menu_roles as r', 'r.menu_id', '=', 'm.id')
            ->whereRaw('LOWER(TRIM(r.role_name)) = ?', [$role])
            ->where('m.is_active', 1)
            ->where('m.menu_path', $routeName)
            ->exists();
    }
}
