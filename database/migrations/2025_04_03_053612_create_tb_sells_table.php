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
        Schema::create('tb_sells', function (Blueprint $table) {
            $table->id();
            $table->string('no_invoice');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->date('date');
            $table->decimal('total_price',12, 2);
            $table->decimal('payment_amount', 12,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_sells');
    }
};
