<?php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbacSeeder extends Seeder
{
    public function run()
    {
        // daftar permission granular sesuai menu/fitur
        $perms = [
            'dashboard.view',
            'users.read',
            'users.create',
            'report.laba.view',
            'report.penjualan.view',
        ];
        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->givePermissionTo(Permission::all());

        $kasir = Role::findOrCreate('kasir', 'web');
        // contoh: kasir hanya boleh lihat dashboard & laporan penjualan
        $kasir->syncPermissions([
            'dashboard.view',
            'report.penjualan.view',
            // NOT: 'report.laba.view'
        ]);

        // contoh assign ke user tertentu
        // $user = \App\Models\User::where('email','kasir@acme.com')->first();
        // $user?->assignRole('kasir');
    }
}
