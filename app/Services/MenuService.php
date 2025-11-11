<?php
namespace App\Services;

use App\Models\Menu;
use Illuminate\Support\Collection;

class MenuService
{
    public function buildForUser($user): array
    {
        $all = Menu::query()->orderBy('order')->get();
        $perms = $user?->getAllPermissions()->pluck('name')->toBase();

        $byParent = $all->groupBy(fn($m) => $m->parent_id ?? 0);

        $fits = function (Menu $m) use ($perms) {
            $req = collect($m->required_permissions ?? []);
            if ($req->isEmpty()) return true; // menu tanpa syarat (folder/parent)
            // item harus memenuhi AND di level-nya
            return $req->every(fn($p) => $perms->contains($p));
        };

        $build = function($parentId) use (&$build, $byParent, $fits) {
            /** @var Collection<int,Menu> $items */
            $items = $byParent->get($parentId ?? 0, collect());
            $out = [];
            foreach ($items as $m) {
                $children = $build($m->id);
                $selfOk = $fits($m);
                $hasOkChild = count($children) > 0;

                // OR di parent: tampil jika diri cocok ATAU ada anak yang cocok
                if (!$selfOk && !$hasOkChild) continue;

                $out[] = [
                    'id' => $m->id,
                    'title' => $m->title,
                    'route' => $m->route,
                    'icon' => $m->icon,
                    'children' => $children,
                ];
            }
            return $out;
        };

        return $build(null);
    }
}
