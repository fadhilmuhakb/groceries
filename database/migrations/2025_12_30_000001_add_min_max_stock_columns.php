<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_product_store_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_product_store_prices', 'min_stock')) {
                $table->unsignedBigInteger('min_stock')->nullable()->after('product_discount');
            }
            if (!Schema::hasColumn('tb_product_store_prices', 'max_stock')) {
                $table->unsignedBigInteger('max_stock')->nullable()->after('min_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_product_store_prices', function (Blueprint $table) {
            if (Schema::hasColumn('tb_product_store_prices', 'min_stock')) {
                $table->dropColumn('min_stock');
            }
            if (Schema::hasColumn('tb_product_store_prices', 'max_stock')) {
                $table->dropColumn('max_stock');
            }
        });
    }
};
