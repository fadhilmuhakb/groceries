<?php

namespace Database\Seeders;

use App\Models\tb_types;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TbTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'type_name' => 'Barang Ritel',
            ],
            [
                'type_name' => 'Makanan dan Minuman',
            ],
            [
                'type_name' => 'Jasa'
            ],
            [
                'type_name' => 'Barang lain - lain'
            ]
        ];

        tb_types::insert($types);
    }
}
