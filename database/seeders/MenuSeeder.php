<?php
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run()
    {
        $dashboard = Menu::create([
            'title' => 'Dashboard', 'route' => 'dashboard.index',
            'order' => 1, 'required_permissions' => ['dashboard.view'],
        ]);

        $users = Menu::create([
            'title' => 'Users', 'icon' => 'fa fa-users',
            'order' => 2, 'required_permissions' => ['users.read']
        ]);
        Menu::create([
            'title' => 'Daftar User', 'route' => 'users.index',
            'parent_id' => $users->id, 'order' => 1, 'required_permissions' => ['users.read']
        ]);
        Menu::create([
            'title' => 'Tambah User', 'route' => 'users.create',
            'parent_id' => $users->id, 'order' => 2, 'required_permissions' => ['users.create']
        ]);

        $reports = Menu::create([
            'title' => 'Laporan', 'icon' => 'fa fa-file',
            'order' => 3, 'required_permissions' => [] // folder/section, kosong = bebas tampil jika ada child
        ]);
        Menu::create([
            'title' => 'Laba', 'route' => 'reports.laba.index',
            'parent_id' => $reports->id, 'order' => 1, 'required_permissions' => ['report.laba.view']
        ]);
        Menu::create([
            'title' => 'Penjualan', 'route' => 'reports.penjualan.index',
            'parent_id' => $reports->id, 'order' => 2, 'required_permissions' => ['report.penjualan.view']
        ]);
    }
}
