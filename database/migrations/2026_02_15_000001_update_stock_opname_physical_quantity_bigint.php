<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tb_stock_opnames') || !Schema::hasColumn('tb_stock_opnames', 'physical_quantity')) {
            return;
        }

        DB::statement(
            'ALTER TABLE tb_stock_opnames MODIFY physical_quantity BIGINT UNSIGNED NOT NULL DEFAULT 0'
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('tb_stock_opnames') || !Schema::hasColumn('tb_stock_opnames', 'physical_quantity')) {
            return;
        }

        DB::statement(
            'ALTER TABLE tb_stock_opnames MODIFY physical_quantity INT NOT NULL DEFAULT 0'
        );
    }
};
