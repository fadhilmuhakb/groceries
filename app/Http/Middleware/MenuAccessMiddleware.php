<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);

        $roleName = trim(strtolower((string)($user->roles ?? '')));
        if ($roleName === 'superadmin') return $next($request); // bebas

        $routeName = optional($request->route())->getName();
        if (!$routeName) return $next($request); // route tanpa name: lepas

        $menuId = DB::table('tb_master_menuses')->where('menu_path', $routeName)->value('id');
        if (!$menuId) return $next($request); // route yg tidak dikelola di menus: lepas

        $allowed = DB::table('tb_master_menu_roles')
            ->where('menu_id', $menuId)
            ->whereRaw('LOWER(role_name) = ?', [$roleName])
            ->exists();

        if ($allowed) return $next($request);

        abort(403, 'Anda tidak memiliki akses ke menu ini.');
    }
}
