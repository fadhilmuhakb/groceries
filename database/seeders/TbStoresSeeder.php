<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TbStoresSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('stores')->insert([
            [
                'store_name' => 'Toko Sembako Makmur',
                'store_address' => 'Jl. Merdeka No.10, Jakarta Pusat',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Grosir Berkah Abadi',
                'store_address' => 'Jl. Raya Darmo No.20, Surabaya',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Toko Murah Meriah',
                'store_address' => 'Jl. Asia Afrika No.5, Bandung',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Sembako Sejahtera',
                'store_address' => 'Jl. Diponegoro No.12, Yogyakarta',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Supermart Nusantara',
                'store_address' => 'Jl. MT Haryono No.45, Medan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Toko Hemat Sentosa',
                'store_address' => 'Jl. S. Parman No.33, Palembang',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Sumber Rejeki Mart',
                'store_address' => 'Jl. Gajah Mada No.88, Semarang',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Grosir Amanah Jaya',
                'store_address' => 'Jl. Hasanuddin No.77, Makassar',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Warung Keluarga Kita',
                'store_address' => 'Jl. Ahmad Yani No.14, Malang',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Pasar Mini Lestari',
                'store_address' => 'Jl. Imam Bonjol No.19, Denpasar',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Toko Sumber Pangan',
                'store_address' => 'Jl. Teuku Umar No.25, Banda Aceh',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Toko Murah Hati',
                'store_address' => 'Jl. Sudirman No.40, Pekanbaru',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Warung Pojok Bahagia',
                'store_address' => 'Jl. Slamet Riyadi No.7, Solo',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Grosir Harapan Makmur',
                'store_address' => 'Jl. Pemuda No.66, Balikpapan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'store_name' => 'Super Sembako Family',
                'store_address' => 'Jl. Panglima Polim No.3, Banjarmasin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
