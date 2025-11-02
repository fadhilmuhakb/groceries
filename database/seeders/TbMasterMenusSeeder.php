<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TbMasterMenusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
                [
                    'menu_name' => 'Laba',
                    'menu_path' => '',
                    'menu_icon' => 'bx-home-circle',
                    'parent_id' => null,
                ],
                [
                    'menu_name' => 'Sales',
                    'menu_path' => '',
                    'menu_icon' => 'bx-user-circle',
                    'parent_id' => null,
                ],
                [
                    'menu_name' => 'Kelola Supplier',
                    'menu_path' => '',
                    'menu_icon' => 'bx-book',
                    'parent_id' => null,
                ],
                [
                    'menu_name' => 'Kelola Toko',
                    'menu_path' => '',
                    'menu_icon' => 'bx-store',
                    'parent_id' => null,
                ],
                [
                    'menu_name' => 'Kelola User',
                    'menu_path' => '',
                    'menu_icon' => 'lni-customer',
                    'parent_id' => null,
                ],
                [
                    'menu_name' => 'Kelola Customer',
                    'menu_path' => '',
                    'menu_icon' => 'lni-customer',
                    'parent_id' => null,
                ],
                [
                    'menu_name' => 'Master Data',
                    'menu_path' => '',
                    'menu_icon' => 'lni-customer',
                    'parent_id' => null,
                ],
            ];
    }
}
