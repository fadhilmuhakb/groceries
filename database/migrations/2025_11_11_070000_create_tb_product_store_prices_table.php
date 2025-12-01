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
        Schema::create('tb_product_store_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('product_discount', 15, 2)->nullable();
            $table->json('tier_prices')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'store_id'], 'product_store_unique');
            $table->foreign('product_id')->references('id')->on('tb_products')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('tb_stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_product_store_prices');
    }
};
