<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTbStockOpnamesTable extends Migration
{
    public function up()
    {
        Schema::create('tb_stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->integer('physical_quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'store_id']);

            $table->foreign('product_id')->references('id')->on('tb_products')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('tb_stores')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tb_stock_opnames');
    }
}
