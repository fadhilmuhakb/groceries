<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\tb_master_menus;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }


public function boot(): void
{
    View::composer(['layouts.app','layouts.sidebar'], function ($view) {
        $user = auth()->user();
        if (!$user) { $view->with('sidebarMenus', collect()); return; }
        $roleName = trim(strtolower((string)($user->roles ?? '')));

        if ($roleName === 'superadmin') {
            $all = tb_master_menus::where('is_active',1)->orderBy('id')->get()->groupBy('parent_id');
            $build = function($pid) use (&$build,$all){
                $items = $all[$pid] ?? collect();
                return $items->map(function($m) use ($build){
                    $m->setRelation('children', $build($m->id));
                    return $m;
                });
            };
            $menus = $build(null);
        } else {
            $menus = tb_master_menus::treeAllowedForRoleName($roleName);
        }

        $view->with('sidebarMenus', $menus);
    });
}

}
