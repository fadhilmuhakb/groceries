<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * SyncService (hybrid: tombol & background job)
 *
 * Alur:
 *  - PUSH antrian lokal (sync_queue) -> POST {base}/api/sync/push
 *  - PULL perubahan server           -> GET  {base}/api/sync/pull?since=...&limit=...
 *  - Apply ke DB lokal (upsert/delete by uuid)
 *
 * Prasyarat lokal:
 *  - Tabel: sync_meta (kolom lama: last_successful_sync) ATAU (key/value)
 *  - Tabel: sync_queue (operation_id, table, op, row JSON)
 *  - Tabel domain punya kolom: uuid (UNIQUE), deleted_at (soft delete), timestamps
 */
class SyncService
{
    /** @var string[] tabel yang TIDAK akan disinkronkan ke lokal */
    protected array $denyTables = [
        'users',
        'password_resets',
        'personal_access_tokens',
        'migrations',
        'failed_jobs',
        'jobs',
        'job_batches',
    ];

    /**
     * Jalankan satu siklus sinkronisasi.
     */
    public function run(): array
    {
        // --- base URL guard ---
        $baseUrl = trim((string) config('sync.base_url', ''));
        if ($baseUrl === '') {
            throw new \RuntimeException(
                "SYNC_BASE_URL belum diset. Isi di .env lalu jalankan: php artisan config:clear && php artisan config:cache"
            );
        }
        if (!preg_match('~^https?://~i', $baseUrl)) {
            $baseUrl = 'http://' . ltrim($baseUrl, '/');
        }
        $baseUrl = rtrim($baseUrl, '/');

        $deviceId = (string) config('sync.device_id', 'offline-device-001');
        $limit    = (int) config('sync.pull_limit', 1000);
        $tables   = trim((string) config('sync.tables', '')); // opsional: "tb_products,tb_brands,..."
        $apiKey   = (string) config('sync.api_key', '');      // opsional, kalau server pakai API key

        // --- since ---
        $since = $this->getSince() ?? '1970-01-01T00:00:00Z';

        // --- PUSH queue ---
        $pushedCount = $this->pushQueue($baseUrl, $deviceId, $apiKey);

        // --- PULL ---
        $params = ['since' => $since, 'limit' => $limit];
        if ($tables !== '') {
            $params['tables'] = $tables;
        }

        $headers = ['X-Device-Id' => $deviceId];
        if ($apiKey !== '') {
            $headers['X-Api-Key'] = $apiKey;
        }

        $response = Http::withHeaders($headers)
            ->acceptJson()
            ->retry(2, 250)
            ->get($baseUrl . '/api/sync/pull', $params);

        $response->throw();

        $payload = $response->json() ?? [];
        $changes = Arr::get($payload, 'changes', []);
        if (!is_array($changes)) {
            $changes = [];
        }

        // --- APPLY (transaksional) ---
        $pulledTotal = 0;
        DB::transaction(function () use ($changes, &$pulledTotal) {
            foreach ($changes as $table => $items) {
                // denylist
                if (in_array($table, $this->denyTables, true)) {
                    Log::info("[Sync] Skip table '{$table}' (denylist).");
                    continue;
                }

                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as $change) {
                    $op      = Arr::get($change, 'op', 'upsert');
                    $rowUuid = Arr::get($change, 'row_uuid');
                    $payload = Arr::get($change, 'payload', []);

                    if (!$rowUuid) {
                        continue; // wajib ada uuid
                    }

                    // tabel harus ada & punya kolom uuid
                    if (!Schema::hasTable($table)) {
                        Log::warning("[Sync] Skip {$op}: table '{$table}' tidak ada di lokal");
                        continue;
                    }
                    if (!Schema::hasColumn($table, 'uuid')) {
                        Log::warning("[Sync] Skip {$op}: kolom 'uuid' tidak ada pada table '{$table}'");
                        continue;
                    }

                    if ($op === 'upsert') {
                        if (!is_array($payload)) {
                            $payload = [];
                        }
                        // isi uuid saat insert
                        $payload['uuid'] = $rowUuid;

                        // normalisasi payload (timestamps, kolom sensitif, dll)
                        $payload = $this->sanitizePayload($table, $payload);

                        DB::table($table)->updateOrInsert(
                            ['uuid' => $rowUuid],
                            $payload
                        );
                        $pulledTotal++;
                    } elseif ($op === 'delete') {
                        // soft delete jika ada kolom deleted_at, kalau tidak ada -> hard delete
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

        // --- update since ---
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
        Log::info('[Sync] summary', $summary);

        return $summary;
    }

    /**
     * Ambil penanda waktu sinkron (support kolom lama atau key/value)
     */
    protected function getSince(): ?string
    {
        if (!Schema::hasTable('sync_meta')) {
            return null;
        }

        // skema lama: kolom last_successful_sync
        if (Schema::hasColumn('sync_meta', 'last_successful_sync')) {
            try {
                $val = DB::table('sync_meta')->value('last_successful_sync');
                return $val ?: null;
            } catch (Throwable $e) {
                Log::warning('[Sync] getSince (kolom) gagal: ' . $e->getMessage());
            }
        }

        // skema key/value
        try {
            if (Schema::hasColumn('sync_meta', 'key') && Schema::hasColumn('sync_meta', 'value')) {
                $val = DB::table('sync_meta')->where('key', 'last_successful_sync')->value('value');
                return $val ?: null;
            }
        } catch (Throwable $e) {
            Log::warning('[Sync] getSince (key/value) gagal: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Simpan penanda waktu sinkron (support kolom lama atau key/value)
     */
    protected function setSince(string $nextSince): void
    {
        if (!Schema::hasTable('sync_meta')) {
            return;
        }

        // skema lama
        if (Schema::hasColumn('sync_meta', 'last_successful_sync')) {
            try {
                DB::table('sync_meta')->updateOrInsert(
                    ['id' => 1],
                    ['last_successful_sync' => $nextSince, 'updated_at' => now(), 'created_at' => now()]
                );
                return;
            } catch (Throwable $e) {
                Log::warning('[Sync] setSince (kolom) gagal: ' . $e->getMessage());
            }
        }

        // skema key/value
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
     * Push semua antrian lokal ke server.
     */
    protected function pushQueue(string $baseUrl, string $deviceId, string $apiKey = ''): int
    {
        if (!Schema::hasTable('sync_queue')) {
            return 0;
        }

        $queued = DB::table('sync_queue')->orderBy('id')->get();
        if ($queued->count() === 0) {
            return 0;
        }

        $operations = [];
        foreach ($queued as $row) {
            $rowPayload = $row->row;

            // row disimpan sebagai JSON string? pastikan array
            if (is_string($rowPayload)) {
                $decoded = json_decode($rowPayload, true);
                $rowPayload = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($rowPayload)) {
                $rowPayload = [];
            }

            // minimal uuid
            if (empty($rowPayload['uuid'])) {
                Log::warning("[Sync] Queue item tanpa 'uuid' di table {$row->table}, operation_id {$row->operation_id}");
            }

            $operations[] = [
                'operation_id' => $row->operation_id,
                'table'        => $row->table,
                'op'           => $row->op,
                'row'          => $rowPayload,
            ];
        }

        $headers = ['X-Device-Id' => $deviceId];
        if ($apiKey !== '') {
            $headers['X-Api-Key'] = $apiKey;
        }

        $resp = Http::withHeaders($headers)
            ->acceptJson()
            ->retry(2, 250)
            ->post($baseUrl . '/api/sync/push', ['operations' => $operations]);

        $resp->throw();

        DB::table('sync_queue')->whereIn('id', $queued->pluck('id'))->delete();

        return count($operations);
    }

    /**
     * Sanitasi payload sebelum upsert (normalisasi timestamp, kolom sensitif, dsb).
     * Kamu boleh modifikasi sesuai kebutuhan skema lokal.
     */
    protected function sanitizePayload(string $table, array $payload): array
    {
        // Jangan pernah pakai 'id' dari server (biarkan auto-increment lokal)
        unset($payload['id']);

        // Normalisasi timestamps (dari ISO8601 ke 'Y-m-d H:i:s')
        foreach (['created_at','updated_at','deleted_at','email_verified_at'] as $ts) {
            if (!empty($payload[$ts])) {
                try {
                    $payload[$ts] = Carbon::parse($payload[$ts])->format('Y-m-d H:i:s');
                } catch (Throwable $e) {
                    // biarkan apa adanya jika gagal parse
                }
            }
        }

        // Khusus beberapa tabel, kamu bisa custom
        if ($table === 'users') {
            // seharusnya tabel ini di-skip via denylist.
            // Kalau tetap masuk (mis-konfigurasi), amankan saja:
            if (empty($payload['password'])) {
                $payload['password'] = Hash::make(Str::random(32));
            }
            // token optional
            if (array_key_exists('remember_token', $payload) && is_null($payload['remember_token'])) {
                unset($payload['remember_token']);
            }
        }

        return $payload;
    }
}
