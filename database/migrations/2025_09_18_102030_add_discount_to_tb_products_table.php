<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tb_products', function (Blueprint $table) {
            $table->decimal('product_discount', 15,2)->after('selling_price')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_products', function (Blueprint $table) {
            $table->dropColumn('product_discount');
        });
    }
};
