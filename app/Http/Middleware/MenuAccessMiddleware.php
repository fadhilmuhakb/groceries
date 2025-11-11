<?php

namespace App\Http\Middleware;

use App\Support\MenuHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class MenuAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $roleName = trim(strtolower((string) ($user->roles ?? '')));
        if ($roleName === 'superadmin') {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();
        if (!$routeName) {
            return $next($request);
        }

        $hasMenuConfig = DB::table('tb_master_menuses')
            ->where('menu_path', $routeName)
            ->where('is_active', 1)
            ->exists();

        if (!$hasMenuConfig) {
            return $next($request);
        }

        $allowed = DB::table('tb_master_menuses as m')
            ->join('tb_master_menu_roles as r', 'r.menu_id', '=', 'm.id')
            ->where('m.menu_path', $routeName)
            ->where('m.is_active', 1)
            ->whereRaw('LOWER(TRIM(r.role_name)) = ?', [$roleName])
            ->exists();

        if ($allowed) {
            return $next($request);
        }

        $warning = 'Akses ke menu tersebut telah dicabut. Anda dialihkan ke menu yang masih tersedia.';

        $fallbackRoute = MenuHelper::firstAllowedRouteFor($roleName);
        if ($fallbackRoute && Route::has($fallbackRoute) && $fallbackRoute !== $routeName) {
            return redirect()->route($fallbackRoute)->with('warning', $warning);
        }

        if (MenuHelper::roleHasRoute('home', $roleName) && Route::has('home') && $routeName !== 'home') {
            return redirect()->route('home')->with('warning', $warning);
        }

        abort(403, 'Anda tidak memiliki akses ke menu ini.');
    }
}
