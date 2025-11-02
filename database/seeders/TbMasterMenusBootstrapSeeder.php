<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TbMasterMenusBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // helper insert parent/child
            $insert = function (array $row) {
                return DB::table('tb_master_menuses')->insertGetId(array_merge([
                    'menu_icon'  => null,
                    'parent_id'  => null,
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $row));
            };

            // ---- Parents / Top-level ----
            $dashboardId = $insert([
                'menu_name' => 'Laba',
                'menu_path' => 'home',
                'menu_icon' => 'bx bx-home-circle',
            ]);

            $salesId = $insert([
                'menu_name' => 'Sales',
                'menu_path' => 'sales.index',
                'menu_icon' => 'bx bx-user-circle',
            ]);

            $supplierId = $insert([
                'menu_name' => 'Kelola Supplier',
                'menu_path' => 'supplier.index',
                'menu_icon' => 'bx bx-book',
            ]);

            $storeId = $insert([
                'menu_name' => 'Kelola Toko',
                'menu_path' => 'store.index',
                'menu_icon' => 'bx bx-store',
            ]);

            $userId = $insert([
                'menu_name' => 'Kelola User',
                'menu_path' => 'user.index',
                'menu_icon' => 'bx bx-user',
            ]);

            $customerId = $insert([
                'menu_name' => 'Kelola Customer',
                'menu_path' => 'customer.index',
                'menu_icon' => 'lni lni-customer',
            ]);

            $masterId = $insert([
                'menu_name' => 'Master Data',
                'menu_path' => null, // parent
                'menu_icon' => 'bx bx-category',
            ]);

            $stockOpnameId = $insert([
                'menu_name' => 'Stock Opname',
                'menu_path' => null, // parent
                'menu_icon' => 'bx bx-category',
            ]);

            $laporanId = $insert([
                'menu_name' => 'Laporan',
                'menu_path' => null, // parent
                'menu_icon' => 'bx bx-building',
            ]);

            $settingsId = $insert([
                'menu_name' => 'Settings',
                'menu_path' => null, // parent
                'menu_icon' => 'bx bx-category',
            ]);

            // ---- Children ----
            // Master Data children
            $insert([
                'menu_name' => 'Kelola Jenis',
                'menu_path' => 'master-types.index',
                'parent_id' => $masterId,
            ]);
            $insert([
                'menu_name' => 'Kelola Merek',
                'menu_path' => 'master-brand.index',
                'parent_id' => $masterId,
            ]);
            $insert([
                'menu_name' => 'Kelola Produk',
                'menu_path' => 'master-product.index',
                'parent_id' => $masterId,
            ]);
            $insert([
                'menu_name' => 'Kelola Satuan',
                'menu_path' => 'master-unit.index',
                'parent_id' => $masterId,
            ]);

            // Stock Opname children
            $insert([
                'menu_name' => 'Pembelian',
                'menu_path' => 'purchase.index',
                'parent_id' => $stockOpnameId,
            ]);
            $insert([
                'menu_name' => 'Inventory',
                'menu_path' => 'inventory.index',
                'parent_id' => $stockOpnameId,
            ]);
            $insert([
                'menu_name' => 'Barang Keluar',
                'menu_path' => 'sell.index',
                'parent_id' => $stockOpnameId,
            ]);

            // Laporan children
            $insert([
                'menu_name' => 'Laporan Kasir',
                'menu_path' => 'report.index',
                'parent_id' => $laporanId,
            ]);
        });
    }
}
