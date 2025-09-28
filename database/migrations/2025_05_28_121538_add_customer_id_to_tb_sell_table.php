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
        Schema::table('tb_sells', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->after('store_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_sell', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });
    }
};
