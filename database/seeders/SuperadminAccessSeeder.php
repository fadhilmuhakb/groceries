<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuperadminAccessSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $roleName = 'superadmin'; // pakai users.roles (lowercase)

            // Ambil semua menu aktif (atau semua menu, kalau mau hilangkan where is_active)
            $menuIds = DB::table('tb_master_menuses')->where('is_active', 1)->pluck('id');

            foreach ($menuIds as $menuId) {
                DB::table('tb_master_menu_roles')->updateOrInsert(
                    ['menu_id' => $menuId, 'role_name' => $roleName],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        });
    }
}
