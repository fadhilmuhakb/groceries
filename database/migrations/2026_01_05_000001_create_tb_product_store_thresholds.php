<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_product_store_thresholds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('min_stock')->nullable();
            $table->unsignedBigInteger('max_stock')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'store_id'], 'uq_prod_store_thresh');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_product_store_thresholds');
    }
};
