<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tb_products', function (Blueprint $table) {
            // Letakkan setelah selling_price atau product_discount sesuai struktur tabelmu
            if (!Schema::hasColumn('tb_products', 'tier_prices')) {
                $table->json('tier_prices')->nullable()->after('product_discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_products', function (Blueprint $table) {
            if (Schema::hasColumn('tb_products', 'tier_prices')) {
                $table->dropColumn('tier_prices');
            }
        });
    }
};
