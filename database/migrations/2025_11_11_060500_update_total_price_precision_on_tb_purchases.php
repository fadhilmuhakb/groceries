<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Allow very large totals (with cents) without overflow errors.
        DB::statement('ALTER TABLE tb_purchases MODIFY total_price DECIMAL(20,2) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE tb_purchases MODIFY total_price BIGINT UNSIGNED NULL');
    }
};
