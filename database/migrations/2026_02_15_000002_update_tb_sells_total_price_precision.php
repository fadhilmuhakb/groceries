<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Match purchase totals to avoid overflow on large stock adjustments.
        DB::statement('ALTER TABLE tb_sells MODIFY total_price DECIMAL(38,2) NULL');
        DB::statement('ALTER TABLE tb_sells MODIFY payment_amount DECIMAL(38,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tb_sells MODIFY total_price DECIMAL(12,2) NULL');
        DB::statement('ALTER TABLE tb_sells MODIFY payment_amount DECIMAL(12,2) NULL');
    }
};
