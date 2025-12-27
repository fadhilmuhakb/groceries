<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_purchases', 'created_by')) {
                $table->uuid('created_by')->nullable()->after('store_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('tb_purchases', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });
    }
};
