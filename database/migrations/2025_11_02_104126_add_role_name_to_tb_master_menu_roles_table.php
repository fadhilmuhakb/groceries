<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tb_master_menu_roles', function (Blueprint $t) {
            // tambahkan kolom role_name (index)
            $t->string('role_name')->after('menu_id');
            // cegah duplikat (menu_id, role_name)
            $t->unique(['menu_id', 'role_name'], 'uq_menu_role_name');
        });

        // NOTE: biarkan kolom role_id ada dulu (backward compatible). Nanti kalau sudah pasti
        // tidak dipakai, boleh di-drop pada migration terpisah.
    }

    public function down(): void
    {
        Schema::table('tb_master_menu_roles', function (Blueprint $t) {
            $t->dropUnique('uq_menu_role_name');
            $t->dropColumn('role_name');
        });
    }
};
