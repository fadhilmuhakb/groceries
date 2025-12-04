<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStockMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $table = 'tb_master_menuses';

            // pastikan parent "Stock Opname" ada
            $parentId = DB::table($table)->where('menu_name', 'Stock Opname')->value('id');
            if (!$parentId) {
                $parentId = DB::table($table)->insertGetId([
                    'menu_name' => 'Stock Opname',
                    'menu_path' => null,
                    'menu_icon' => 'bx bx-category',
                    'parent_id' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $exists = DB::table($table)->where('menu_path', 'order-stock.index')->exists();
            if (!$exists) {
                DB::table($table)->insert([
                    'menu_name' => 'Order Stock',
                    'menu_path' => 'order-stock.index',
                    'menu_icon' => 'bx bx-cart-download',
                    'parent_id' => $parentId,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }
}
