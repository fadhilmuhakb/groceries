<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Lepas foreign key kalau ada (nama default Laravel)
        try {
            Schema::table('tb_master_menu_roles', function (Blueprint $table) {
                $table->dropForeign(['role_id']); // abaikan jika tidak ada FK
            });
        } catch (\Throwable $e) {}

        Schema::table('tb_master_menu_roles', function (Blueprint $table) {
            // Jadikan nullable (butuh doctrine/dbal)
            $table->unsignedBigInteger('role_id')->nullable()->default(null)->change();
            // (opsional) pastikan unique untuk (menu_id, role_name) agar tidak dobel
            if (!Schema::hasColumn('tb_master_menu_roles', 'role_name')) {
                $table->string('role_name')->after('menu_id');
            }
        });

        // Tambah unique utk kombinasi menu_id + role_name (lewati jika sudah ada)
        try {
            Schema::table('tb_master_menu_roles', function (Blueprint $table) {
                $table->unique(['menu_id', 'role_name'], 'uq_menu_role_name');
            });
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::table('tb_master_menu_roles', function (Blueprint $table) {
            // balikin kalau mau (NOT NULL) — opsional
            $table->dropUnique('uq_menu_role_name');
            $table->unsignedBigInteger('role_id')->nullable(false)->change();
        });
        // (opsional) tambah kembali FK jika kamu memang pakai tabel tb_master_roles
        // Schema::table('tb_master_menu_roles', function (Blueprint $table) {
        //     $table->foreign('role_id')->references('id')->on('tb_master_roles')->cascadeOnDelete();
        // });
    }
};
