<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sync_changes', function (Blueprint $table) {
      $table->id();
      $table->string('table');          // contoh: tb_products
      $table->uuid('row_uuid');         // UUID record yang berubah
      $table->enum('op', ['upsert','delete']);
      $table->json('payload')->nullable(); // data row (untuk upsert)
      $table->timestamp('changed_at');  // biasanya = updated_at
      $table->string('device_id')->nullable(); // asal perubahan
      $table->timestamps();
      $table->index(['table','changed_at']);
    });

    Schema::create('sync_operations', function (Blueprint $table) {
      $table->id();
      $table->uuid('operation_id')->unique(); // dari klien
      $table->string('table');
      $table->uuid('row_uuid');
      $table->string('device_id')->nullable();
      $table->timestamp('applied_at');
      $table->json('result')->nullable();
      $table->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('sync_changes');
    Schema::dropIfExists('sync_operations');
  }
};
