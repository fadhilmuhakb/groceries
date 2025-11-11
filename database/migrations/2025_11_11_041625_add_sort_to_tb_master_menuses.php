<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('tb_master_menuses', 'sort')) {
            Schema::table('tb_master_menuses', function (Blueprint $table) {
                $table->integer('sort')->default(0)->after('menu_icon');
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('tb_master_menuses', 'sort')) {
            Schema::table('tb_master_menuses', function (Blueprint $table) {
                $table->dropColumn('sort');
            });
        }
    }
};
