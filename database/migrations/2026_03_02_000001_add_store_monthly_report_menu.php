<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tb_master_menuses')) {
            return;
        }

        DB::transaction(function () {
            $now = now();

            $parentId = DB::table('tb_master_menuses')
                ->where('menu_name', 'Laporan')
                ->value('id');

            if (!$parentId) {
                $parentRow = [
                    'menu_name' => 'Laporan',
                    'menu_path' => null,
                    'menu_icon' => 'bx bx-building',
                    'parent_id' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('tb_master_menuses', 'sort')) {
                    $parentRow['sort'] = 0;
                }
                $parentId = DB::table('tb_master_menuses')->insertGetId($parentRow);
            }

            $menuPath = 'report.store.monthly';
            $menuId = DB::table('tb_master_menuses')
                ->where('menu_path', $menuPath)
                ->value('id');

            if (!$menuId) {
                $menuRow = [
                    'menu_name' => 'Laporan Toko',
                    'menu_path' => $menuPath,
                    'menu_icon' => 'bx bx-store',
                    'parent_id' => $parentId,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('tb_master_menuses', 'sort')) {
                    $menuRow['sort'] = 0;
                }
                $menuId = DB::table('tb_master_menuses')->insertGetId($menuRow);
            }

            if (!$menuId) {
                return;
            }

            if (!Schema::hasTable('tb_master_menu_roles')) {
                return;
            }

            if (!Schema::hasColumn('tb_master_menu_roles', 'role_name')) {
                return;
            }

            $sourceMenuId = DB::table('tb_master_menuses')
                ->where('menu_path', 'report.index')
                ->value('id');

            $roleNames = [];
            if ($sourceMenuId) {
                $roleNames = DB::table('tb_master_menu_roles')
                    ->where('menu_id', $sourceMenuId)
                    ->pluck('role_name')
                    ->filter()
                    ->map(fn ($v) => strtolower(trim((string) $v)))
                    ->unique()
                    ->values()
                    ->all();
            }

            if (empty($roleNames)) {
                $roleNames = ['superadmin'];
            }

            foreach ($roleNames as $roleName) {
                DB::table('tb_master_menu_roles')->updateOrInsert(
                    ['menu_id' => $menuId, 'role_name' => $roleName],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tb_master_menuses')) {
            return;
        }

        $menuId = DB::table('tb_master_menuses')
            ->where('menu_path', 'report.store.monthly')
            ->value('id');

        if ($menuId && Schema::hasTable('tb_master_menu_roles')) {
            DB::table('tb_master_menu_roles')
                ->where('menu_id', $menuId)
                ->delete();
        }

        if ($menuId) {
            DB::table('tb_master_menuses')
                ->where('id', $menuId)
                ->delete();
        }
    }
};
