<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TbBrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $brands = [
            'Kapal Api',
            'Indomie',
            'Sedaap',
            'ABC',
            'Tango',
            'Ultra Milk',
            'Teh Botol Sosro',
            'Milo',
            'Beng Beng',
            'Sari Roti',
        ];

        $data = array_map(function ($brand) use ($now) {
            return [
                'brand_name' => $brand,
                'description' => $brand,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $brands);

        DB::table('tb_brands')->insert($data);
    }
}
