<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Jika tabel belum ada: buat skema key/value
        if (!Schema::hasTable('sync_meta')) {
            Schema::create('sync_meta', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            // seed nilai awal
            DB::table('sync_meta')->updateOrInsert(
                ['key' => 'last_successful_sync'],
                ['value' => '1970-01-01T00:00:00Z', 'created_at' => now(), 'updated_at' => now()]
            );
            return;
        }

        // Tabel sudah ada → deteksi skema yang dipakai dan sesuaikan
        $hasId = Schema::hasColumn('sync_meta', 'id');
        $hasKey = Schema::hasColumn('sync_meta', 'key');
        $hasValue = Schema::hasColumn('sync_meta', 'value');
        $hasLss = Schema::hasColumn('sync_meta', 'last_successful_sync');

        // CASE A: sudah pakai key/value → tidak perlu apa-apa (pastikan seed ada)
        if ($hasKey && $hasValue) {
            // seed kalau belum ada
            $exists = DB::table('sync_meta')->where('key', 'last_successful_sync')->exists();
            if (!$exists) {
                DB::table('sync_meta')->updateOrInsert(
                    ['key' => 'last_successful_sync'],
                    ['value' => '1970-01-01T00:00:00Z', 'created_at' => now(), 'updated_at' => now()]
                );
            }
            return;
        }

        // CASE B: skema lama dengan kolom last_successful_sync (tanpa key/value)
        if ($hasLss) {
            // Tambahkan key/value bila belum ada
            if (!$hasKey || !$hasValue) {
                Schema::table('sync_meta', function (Blueprint $table) use ($hasKey, $hasValue) {
                    if (!$hasKey)   $table->string('key')->nullable()->after('last_successful_sync');
                    if (!$hasValue) $table->text('value')->nullable()->after('key');
                });
            }

            // Migrasi data dari kolom last_successful_sync ke key/value
            try {
                $val = DB::table('sync_meta')->value('last_successful_sync');
                if ($val) {
                    DB::table('sync_meta')->updateOrInsert(
                        ['key' => 'last_successful_sync'],
                        ['value' => $val, 'updated_at' => now(), 'created_at' => now()]
                    );
                } else {
                    DB::table('sync_meta')->updateOrInsert(
                        ['key' => 'last_successful_sync'],
                        ['value' => '1970-01-01T00:00:00Z', 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            } catch (\Throwable $e) {
                // fallback seed
                DB::table('sync_meta')->updateOrInsert(
                    ['key' => 'last_successful_sync'],
                    ['value' => '1970-01-01T00:00:00Z', 'updated_at' => now(), 'created_at' => now()]
                );
            }

            return;
        }

        // CASE C: tabel exist tapi strukturnya lain (tidak ada id, tidak ada key/value, tidak ada last_successful_sync)
        // Tambahkan key/value secara aman
        Schema::table('sync_meta', function (Blueprint $table) use ($hasKey, $hasValue) {
            if (!$hasKey)   $table->string('key')->nullable();
            if (!$hasValue) $table->text('value')->nullable();
            if (!Schema::hasColumn('sync_meta', 'created_at')) $table->timestamps();
        });

        // Seed nilai awal
        $exists = DB::table('sync_meta')->where('key', 'last_successful_sync')->exists();
        if (!$exists) {
            DB::table('sync_meta')->updateOrInsert(
                ['key' => 'last_successful_sync'],
                ['value' => '1970-01-01T00:00:00Z', 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Tidak perlu revert struktur karena ini reconcile; biarkan apa adanya
        // (opsional) Jika ingin, Anda bisa drop kolom key/value yang ditambahkan.
    }
};
