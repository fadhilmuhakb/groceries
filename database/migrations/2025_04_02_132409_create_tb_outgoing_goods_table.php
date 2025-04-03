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
        Schema::create('tb_outgoing_goods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tb_products');
            $table->date('date');
            $table->integer('quantity_out');
            $table->string('recorded_by');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_outgoing_goods');
    }
};
