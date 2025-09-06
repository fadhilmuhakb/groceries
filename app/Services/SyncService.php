<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * SyncService
 *
 * - Push antrian lokal (sync_queue) ke server: /api/sync/push
 * - Pull perubahan dari server: /api/sync/pull
 * - Terapkan perubahan ke DB lokal (upsert/delete by uuid)
 *
 * Prasyarat:
 *  - Tabel lokal:
 *      sync_meta  (skema kolom lama: id + last_successful_sync) ATAU (skema key/value)
 *      sync_queue (operation_id, table, op, row JSON)
 *  - Tabel-tabel domain lokal memiliki kolom: uuid, deleted_at (untuk soft delete), timestamps
 *  - config/sync.php:
 *      base_url, device_id, pull_limit, tables (opsional: "tb_products,tb_brands,...")
 */
class SyncService
{
    /**
     * Jalankan satu siklus sinkronisasi: push -> pull -> apply -> update since
     *
     * @return array Ringkasan hasil
     * @throws \Throwable
     */
    public function run(): array
    {
        $baseUrl  = rtrim((string) config('sync.base_url'), '/');
        $deviceId = (string) config('sync.device_id', 'offline-device-001');
        $limit    = (int) config('sync.pull_limit', 1000);
        $tables   = trim((string) config('sync.tables', '')); // opsional

        // 1) Ambil penanda waktu terakhir sinkron
        $since = $this->getSince() ?? '1970-01-01T00:00:00Z';

        // 2) PUSH antrian lokal (jika ada)
        $pushedCount = $this->pushQueue($baseUrl, $deviceId);

        // 3) PULL perubahan dari server
        $params = ['since' => $since, 'limit' => $limit];
        if ($tables !== '') {
            $params['tables'] = $tables; // contoh: "tb_products,tb_brands"
        }

        $response = Http::withHeaders(['X-Device-Id' => $deviceId])
            ->acceptJson()
            ->retry(2, 250) // retry ringan
            ->get($baseUrl . '/api/sync/pull', $params);

        // Lempar exception kalau HTTP error
        $response->throw();

        $payload = $response->json() ?? [];
        $changes = Arr::get($payload, 'changes', []);
        if (!is_array($changes)) {
            $changes = [];
        }

        // 4) Terapkan perubahan ke DB lokal (transaksional)
        $pulledTotal = 0;
        DB::transaction(function () use ($changes, &$pulledTotal) {
            foreach ($changes as $table => $items) {
                if (!is_array($items)) {
                    continue;
                }
                foreach ($items as $change) {
                    $op      = Arr::get($change, 'op', 'upsert');
                    $rowUuid = Arr::get($change, 'row_uuid');
                    $payload = Arr::get($change, 'payload', []);

                    if (!$rowUuid) {
                        continue; // skip jika tidak ada uuid
                    }

                    // Upsert by uuid
                    if ($op === 'upsert') {
                        if (!is_array($payload)) {
                            $payload = [];
                        }
                        // Pastikan kolom uuid ikut terisi saat insert
                        $payload['uuid'] = $rowUuid;

                        // Jika tabel tidak ada di lokal, skip dengan aman
                        if (!Schema::hasTable($table)) {
                            Log::warning("[Sync] Skip upsert: table '$table' tidak ada di lokal");
                            continue;
                        }

                        // Jika tabel tidak punya kolom 'uuid', skip
                        if (!Schema::hasColumn($table, 'uuid')) {
                            Log::warning("[Sync] Skip upsert: kolom 'uuid' tidak ada pada table '$table'");
                            continue;
                        }

                        DB::table($table)->updateOrInsert(
                            ['uuid' => $rowUuid],
                            $payload
                        );
                        $pulledTotal++;
                    }
                    // Soft delete by uuid
                    elseif ($op === 'delete') {
                        if (!Schema::hasTable($table)) {
                            Log::warning("[Sync] Skip delete: table '$table' tidak ada di lokal");
                            continue;
                        }
                        if (!Schema::hasColumn($table, 'uuid')) {
                            Log::warning("[Sync] Skip delete: kolom 'uuid' tidak ada pada table '$table'");
                            continue;
                        }
                        // Jika tabel tidak punya kolom deleted_at, fallback: hard delete
                        if (Schema::hasColumn($table, 'deleted_at')) {
                            DB::table($table)->where('uuid', $rowUuid)->update(['deleted_at' => now()]);
                        } else {
                            DB::table($table)->where('uuid', $rowUuid)->delete();
                        }
                        $pulledTotal++;
                    }
                }
            }
        });

        // 5) Update penanda waktu next_since
        $nextSince = Arr::get($payload, 'next_since');
        if ($nextSince) {
            $this->setSince($nextSince);
        }

        $summary = [
            'pushed' => $pushedCount,
            'pulled' => $pulledTotal,
            'since'  => $since,
            'next'   => $nextSince ?: $since,
        ];

        // Opsional: logging ringkas
        Log::info('[Sync] summary', $summary);

        return $summary;
    }

    /**
     * Baca penanda waktu terakhir sinkronisasi.
     * Mendukung 2 skema:
     *  - Kolom lama: sync_meta.last_successful_sync
     *  - Skema key/value: sync_meta { key, value }
     *
     * @return string|null
     */
   protected function getSince(): ?string
{
    if (!Schema::hasTable('sync_meta')) return null;

    if (Schema::hasColumn('sync_meta','last_successful_sync')) {
        return DB::table('sync_meta')->value('last_successful_sync') ?: null;
    }

    if (Schema::hasColumn('sync_meta','key') && Schema::hasColumn('sync_meta','value')) {
        return DB::table('sync_meta')->where('key','last_successful_sync')->value('value') ?: null;
    }

    return null;
}
    /**
     * Simpan penanda waktu sinkronisasi terakhir.
     * Mendukung 2 skema:
     *  - Kolom lama: sync_meta.last_successful_sync (id=1)
     *  - Skema key/value: sync_meta { key: last_successful_sync, value: ... }
     *
     * @param  string  $nextSince  ISO8601
     * @return void
     */
    protected function setSince(string $nextSince): void
    {
        if (!Schema::hasTable('sync_meta')) {
            return;
        }

        // Skema lama: kolom last_successful_sync
        if (Schema::hasColumn('sync_meta', 'last_successful_sync')) {
            try {
                // Asumsikan ada kolom id sebagai PK
                DB::table('sync_meta')->updateOrInsert(
                    ['id' => 1],
                    ['last_successful_sync' => $nextSince, 'updated_at' => now(), 'created_at' => now()]
                );
                return;
            } catch (Throwable $e) {
                Log::warning('[Sync] setSince (kolom) gagal: ' . $e->getMessage());
            }
        }

        // Skema key/value
        try {
            if (Schema::hasColumn('sync_meta', 'key') && Schema::hasColumn('sync_meta', 'value')) {
                DB::table('sync_meta')->updateOrInsert(
                    ['key' => 'last_successful_sync'],
                    ['value' => $nextSince, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        } catch (Throwable $e) {
            Log::warning('[Sync] setSince (key/value) gagal: ' . $e->getMessage());
        }
    }

    /**
     * Kirim semua antrian lokal (sync_queue) ke server /api/sync/push.
     * Setelah sukses, hapus dari antrian.
     *
     * @param  string  $baseUrl
     * @param  string  $deviceId
     * @return int jumlah operasi yang dipush
     */
    protected function pushQueue(string $baseUrl, string $deviceId): int
    {
        if (!Schema::hasTable('sync_queue')) {
            return 0;
        }

        $queued = DB::table('sync_queue')->orderBy('id')->get();
        if ($queued->count() === 0) {
            return 0;
        }

        // Bentuk payload 'operations'
        $operations = [];
        foreach ($queued as $row) {
            $rowPayload = $row->row;
            // Pastikan JSON -> array
            if (is_string($rowPayload)) {
                $decoded = json_decode($rowPayload, true);
                $rowPayload = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($rowPayload)) {
                $rowPayload = [];
            }

            // Minimal butuh uuid pada row
            if (empty($rowPayload['uuid'])) {
                // Kalau tidak ada uuid, skip & log
                Log::warning("[Sync] Queue item tanpa 'uuid' di table {$row->table}, operation_id {$row->operation_id}");
            }

            $operations[] = [
                'operation_id' => $row->operation_id,
                'table'        => $row->table,
                'op'           => $row->op,
                'row'          => $rowPayload,
            ];
        }

        // Push ke server
        $resp = Http::withHeaders(['X-Device-Id' => $deviceId])
            ->acceptJson()
            ->retry(2, 250)
            ->post($baseUrl . '/api/sync/push', ['operations' => $operations]);

        // Kalau error HTTP, lempar supaya ketangkap di caller
        $resp->throw();

        // Hapus antrian yang sudah terkirim
        DB::table('sync_queue')->whereIn('id', $queued->pluck('id'))->delete();

        return count($operations);
    }
    
}
