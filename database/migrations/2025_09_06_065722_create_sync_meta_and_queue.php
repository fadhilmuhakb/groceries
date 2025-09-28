<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Menyimpan status terakhir sinkronisasi
        Schema::create('sync_meta', function (Blueprint $table) {
            $table->string('key')->primary();        // contoh: last_successful_sync
            $table->text('value')->nullable();       // simpan ISO8601 string
            $table->timestamps();
        });

        // Antrian operasi lokal (akan di-push ke server)
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_id')->unique();
            $table->string('table');                 // nama tabel, mis: tb_products
            $table->enum('op', ['upsert','delete']);
            $table->json('row');                     // payload row (JSON)
            $table->timestamps();
            $table->index(['table']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('sync_queue');
        Schema::dropIfExists('sync_meta');
    }
};
