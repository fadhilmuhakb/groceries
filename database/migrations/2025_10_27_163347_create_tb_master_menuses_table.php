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
        Schema::create('tb_master_menuses', function (Blueprint $table) {
            $table->id();
            $table->string('menu_name');
            $table->string('menu_path')->nullable();
            $table->string('menu_icon')->nullable();
            $table->integer('parent_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_master_menuses');
    }
};
