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
                'code' => 'SUP001',
                'name' => 'PT. Sumber Makmur',
                'address' => 'Jl. Kenanga No.12',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'phone_number' => '021-12345678',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP002',
                'name' => 'CV. Berkah Jaya',
                'address' => 'Jl. Melati No.7',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'phone_number' => '022-87654321',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP003',
                'name' => 'PT. Agro Sentosa',
                'address' => 'Jl. Raya Timur No.89',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'phone_number' => '031-23456789',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP004',
                'name' => 'UD. Sinar Terang',
                'address' => 'Jl. Sudirman No.45',
                'city' => 'Medan',
                'province' => 'Sumatera Utara',
                'phone_number' => '061-34567890',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP005',
                'name' => 'CV. Maju Mundur',
                'address' => 'Jl. Gajah Mada No.10',
                'city' => 'Semarang',
                'province' => 'Jawa Tengah',
                'phone_number' => '024-11223344',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP006',
                'name' => 'PT. Citra Niaga',
                'address' => 'Jl. Imam Bonjol No.5',
                'city' => 'Denpasar',
                'province' => 'Bali',
                'phone_number' => '0361-5566778',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP007',
                'name' => 'UD. Tunas Harapan',
                'address' => 'Jl. Diponegoro No.22',
                'city' => 'Makassar',
                'province' => 'Sulawesi Selatan',
                'phone_number' => '0411-9988776',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP008',
                'name' => 'PT. Bintang Timur',
                'address' => 'Jl. Hasanuddin No.19',
                'city' => 'Palembang',
                'province' => 'Sumatera Selatan',
                'phone_number' => '0711-4433221',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP009',
                'name' => 'CV. Mandiri Sejahtera',
                'address' => 'Jl. Ahmad Yani No.3',
                'city' => 'Balikpapan',
                'province' => 'Kalimantan Timur',
                'phone_number' => '0542-6677889',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP010',
                'name' => 'UD. Cahaya Abadi',
                'address' => 'Jl. Pemuda No.55',
                'city' => 'Yogyakarta',
                'province' => 'DI Yogyakarta',
                'phone_number' => '0274-7788990',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
