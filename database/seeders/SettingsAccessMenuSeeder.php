<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsAccessMenuSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $settingsId = DB::table('tb_master_menuses')->where('menu_name','Settings')->value('id');
        if (!$settingsId) {
            $settingsId = DB::table('tb_master_menuses')->insertGetId([
                'menu_name'=>'Settings','menu_path'=>null,'menu_icon'=>'bx bx-category',
                'parent_id'=>null,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now,
            ]);
        }

        $exists = DB::table('tb_master_menuses')->where('menu_path','settings.access.index')->exists();
        if (!$exists) {
            $id = DB::table('tb_master_menuses')->insertGetId([
                'menu_name'=>'Access Control','menu_path'=>'settings.access.index','menu_icon'=>'bx bx-key',
                'parent_id'=>$settingsId,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now,
            ]);
            DB::table('tb_master_menu_roles')->updateOrInsert(
                ['menu_id'=>$id,'role_name'=>'superadmin'],
                ['created_at'=>$now,'updated_at'=>$now]
            );
        }
    }
}
