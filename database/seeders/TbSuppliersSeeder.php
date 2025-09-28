<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TbSuppliersSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('tb_suppliers')->insert([
            [
                'uuid' => '96244971-90ee-4bec-9426-9864148d70ec',
                'code' => 'SO-ADJ',
                'name' => 'Stock Opname Adjustment',
                'address' => 'Di Bandung',
                'city' => 'Bandung',
                'province' => null,
                'phone_number' => '021-12345678',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
