<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockThresholdMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $table = 'tb_master_menuses';

            $masterId = DB::table($table)
                ->where('menu_name', 'Master Data')
                ->value('id');

            if (!$masterId) {
                $masterId = DB::table($table)->insertGetId([
                    'menu_name' => 'Master Data',
                    'menu_path' => null,
                    'menu_icon' => 'bx bx-category',
                    'parent_id' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $menuId = DB::table($table)->where('menu_path', 'stock-threshold.index')->value('id');
            if (!$menuId) {
                $menuId = DB::table($table)->insertGetId([
                    'menu_name' => 'Stok Min/Max',
                    'menu_path' => 'stock-threshold.index',
                    'menu_icon' => 'bx bx-slider-alt',
                    'parent_id' => $masterId,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // beri akses default ke superadmin
            DB::table('tb_master_menu_roles')->updateOrInsert(
                ['menu_id' => $menuId, 'role_name' => 'superadmin'],
                ['created_at' => $now, 'updated_at' => $now]
            );
        });
    }
}
