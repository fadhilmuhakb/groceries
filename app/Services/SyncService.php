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

class SyncService
{
    /** tabel yang TIDAK diikutkan sinkron lokal */
    protected array $denyTables = [
        'users',
        'password_resets',
        'personal_access_tokens',
        'migrations',
        'failed_jobs',
        'jobs',
        'job_batches',
        // tabel engine sync di-skip juga
        'sync_changes',
        'sync_meta',
        'sync_operations',
        'sync_queue',
    ];

    public function run(): array
    {
        // --- Base URL ---
        $baseUrl = trim((string) config('sync.base_url', ''));
        if ($baseUrl === '') {
            throw new \RuntimeException("SYNC_BASE_URL belum diset. Cek .env lalu: php artisan config:clear && php artisan config:cache");
        }
        if (!preg_match('~^https?://~i', $baseUrl)) {
            $baseUrl = 'http://' . ltrim($baseUrl, '/');
        }
        $baseUrl = rtrim($baseUrl, '/');

        $deviceId = (string) config('sync.device_id', 'offline-device-001');
        $limit    = (int) config('sync.pull_limit', 1000);
        $apiKey   = (string) config('sync.api_key', '');
        $tablesCfg= trim((string) config('sync.tables', '')); // "tb_products,tb_brands,..."

        // --- since & cursor (untuk PULL) ---
        $since  = $this->getSince() ?? '1970-01-01T00:00:00Z';
        $cursor = $this->getCursor() ?? 0;

        // --- Build queue dari perubahan lokal (MANUAL only) ---
        // bersihkan config: pecah, trim, buang empty & denyTables
        $tablesArr = array_values(array_filter(array_map('trim', explode(',', $tablesCfg)), function ($t) {
            return $t !== '' && !in_array($t, $this->denyTables, true);
        }));
        $pushSince = $this->getLocalPushSince() ?? '1970-01-01T00:00:00Z';
        if (!empty($tablesArr)) {
            $built = $this->enqueueLocalChanges($tablesArr, $pushSince);
            Log::info("[Sync] built local push queue", ['since' => $pushSince, 'count' => $built, 'tables' => $tablesArr]);
        }

        // --- PUSH queue ---
        $pushedCount = $this->pushQueue($baseUrl, $deviceId, $apiKey);

        // --- PULL ---
        $params = ['since' => $since, 'limit' => $limit];
        if (!empty($tablesArr)) {
            $params['tables'] = implode(',', $tablesArr);
        }
        if ($cursor > 0) {
            $params['cursor'] = $cursor; // tie-breaker aman
        }

        $headers = ['X-Device-Id' => $deviceId];
        if ($apiKey !== '') $headers['X-Api-Key'] = $apiKey;

        $response = Http::withHeaders($headers)->acceptJson()->retry(2, 250)->get($baseUrl . '/api/sync/pull', $params);
        $response->throw();

        $payload = $response->json() ?? [];
        $changes = Arr::get($payload, 'changes', []);
        if (!is_array($changes)) $changes = [];

        // --- APPLY (transaksional) ---
        $pulledTotal = 0;
        DB::transaction(function () use ($changes, &$pulledTotal) {
            foreach ($changes as $table => $items) {
                if (in_array($table, $this->denyTables, true)) continue;
                if (!is_array($items)) continue;

                foreach ($items as $change) {
                    $op      = Arr::get($change, 'op', 'upsert');
                    $rowUuid = Arr::get($change, 'row_uuid');
                    $payload = Arr::get($change, 'payload', []);

                    if (!$rowUuid) continue;
                    if (!Schema::hasTable($table)) continue;
                    if (!Schema::hasColumn($table, 'uuid')) continue;

                    if ($op === 'upsert') {
                        if (!is_array($payload)) $payload = [];
                        $payload['uuid'] = $rowUuid;
                        $payload = $this->sanitizePayload($table, $payload);

                        // dukung explicit null via 'null_fields'
                        $nullFields = [];
                        if (isset($payload['null_fields']) && is_array($payload['null_fields'])) {
                            $nullFields = $payload['null_fields'];
                            unset($payload['null_fields']);
                        }

                        $exists = DB::table($table)->where('uuid', $rowUuid)->exists();
                        if ($exists) {
                            $toUpdate = $this->withoutNulls($payload); // PATCH semantics
                            foreach ($nullFields as $col) {
                                if (Schema::hasColumn($table, $col)) $toUpdate[$col] = null;
                            }
                            if (!empty($toUpdate)) {
                                DB::table($table)->where('uuid', $rowUuid)->update($toUpdate);
                            }
                        } else {
                            foreach ($nullFields as $col) {
                                if (Schema::hasColumn($table, $col)) $payload[$col] = null;
                            }
                            DB::table($table)->insert($payload);
                        }
                        $pulledTotal++;
                    } elseif ($op === 'delete') {
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

        // --- update since & cursor & local scan timestamp ---
        $nextSince  = Arr::get($payload, 'next_since');
        $nextCursor = Arr::get($payload, 'next_cursor');

        if ($nextSince) $this->setSince($nextSince);
        if ($nextCursor !== null) $this->setCursor(is_numeric($nextCursor) ? (int) $nextCursor : null);

        $this->setLocalPushSince(now()->toIso8601String());

        $summary = [
            'pushed'       => $pushedCount,
            'pulled'       => $pulledTotal,
            'since'        => $since,
            'next'         => $nextSince ?: $since,
            'cursor'       => $cursor,
            'next_cursor'  => $nextCursor,
        ];
        Log::info('[Sync] summary', $summary);

        return $summary;
    }

    // ---------------- Meta helpers ----------------

    protected function getSince(): ?string
    {
        if (!Schema::hasTable('sync_meta')) return null;

        if (Schema::hasColumn('sync_meta', 'last_successful_sync')) {
            try { $v = DB::table('sync_meta')->value('last_successful_sync'); return $v ?: null; }
            catch (Throwable $e) { Log::warning('[Sync] getSince (kolom): '.$e->getMessage()); }
        }
        try {
            if (Schema::hasColumn('sync_meta', 'key') && Schema::hasColumn('sync_meta', 'value')) {
                $v = DB::table('sync_meta')->where('key','last_successful_sync')->value('value'); return $v ?: null;
            }
        } catch (Throwable $e) { Log::warning('[Sync] getSince (kv): '.$e->getMessage()); }
        return null;
    }

    protected function setSince(string $nextSince): void
    {
        if (!Schema::hasTable('sync_meta')) return;
        if (Schema::hasColumn('sync_meta', 'last_successful_sync')) {
            try {
                DB::table('sync_meta')->updateOrInsert(['id'=>1], ['last_successful_sync'=>$nextSince,'updated_at'=>now(),'created_at'=>now()]);
                return;
            } catch (Throwable $e) { Log::warning('[Sync] setSince (kolom): '.$e->getMessage()); }
        }
        try {
            if (Schema::hasColumn('sync_meta','key') && Schema::hasColumn('sync_meta','value')) {
                DB::table('sync_meta')->updateOrInsert(['key'=>'last_successful_sync'], ['value'=>$nextSince,'updated_at'=>now(),'created_at'=>now()]);
            }
        } catch (Throwable $e) { Log::warning('[Sync] setSince (kv): '.$e->getMessage()); }
    }

    protected function getCursor(): ?int
    {
        try {
            if (Schema::hasTable('sync_meta') && Schema::hasColumn('sync_meta','key') && Schema::hasColumn('sync_meta','value')) {
                $v = DB::table('sync_meta')->where('key','last_cursor_id')->value('value');
                return ($v !== null && $v !== '') ? (int) $v : null;
            }
        } catch (Throwable $e) { Log::warning('[Sync] getCursor: '.$e->getMessage()); }
        return null;
    }

    protected function setCursor(?int $cursor): void
    {
        try {
            if (Schema::hasTable('sync_meta') && Schema::hasColumn('sync_meta','key') && Schema::hasColumn('sync_meta','value')) {
                if ($cursor === null) {
                    DB::table('sync_meta')->where('key','last_cursor_id')->delete();
                } else {
                    DB::table('sync_meta')->updateOrInsert(['key'=>'last_cursor_id'], ['value'=>(string)$cursor,'updated_at'=>now(),'created_at'=>now()]);
                }
            }
        } catch (Throwable $e) { Log::warning('[Sync] setCursor: '.$e->getMessage()); }
    }

    protected function getLocalPushSince(): ?string
    {
        try {
            if (Schema::hasTable('sync_meta') && Schema::hasColumn('sync_meta','key') && Schema::hasColumn('sync_meta','value')) {
                $v = DB::table('sync_meta')->where('key','last_local_change_scan')->value('value'); return $v ?: null;
            }
        } catch (Throwable $e) { Log::warning('[Sync] getLocalPushSince: '.$e->getMessage()); }
        return null;
    }

    protected function setLocalPushSince(string $iso): void
    {
        try {
            if (Schema::hasTable('sync_meta') && Schema::hasColumn('sync_meta','key') && Schema::hasColumn('sync_meta','value')) {
                DB::table('sync_meta')->updateOrInsert(['key'=>'last_local_change_scan'], ['value'=>$iso,'updated_at'=>now(),'created_at'=>now()]);
            }
        } catch (Throwable $e) { Log::warning('[Sync] setLocalPushSince: '.$e->getMessage()); }
    }

    // ---------------- Manual push queue builder ----------------

    protected function enqueueLocalChanges(array $tablesArr, string $sinceIso): int
    {
        $since = Carbon::parse($sinceIso);
        $inserted = 0;

        foreach ($tablesArr as $tbl) {
            $tbl = trim($tbl);
            if ($tbl === '' || in_array($tbl, $this->denyTables, true)) continue;
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'uuid')) continue;

            // UPSERT: updated_at > since (fallback created_at)
            $q = DB::table($tbl);
            if (Schema::hasColumn($tbl, 'updated_at'))      $q->where('updated_at', '>', $since);
            elseif (Schema::hasColumn($tbl, 'created_at'))  $q->where('created_at', '>', $since);
            else continue;

            foreach ($q->get() as $r) {
                if (empty($r->uuid)) continue;
                DB::table('sync_queue')->insert([
                    'operation_id' => (string) Str::uuid(),
                    'table'        => $tbl,
                    'op'           => 'upsert',
                    'row'          => json_encode((array) $r, JSON_UNESCAPED_UNICODE),
                    'created_at'   => now(),
                ]);
                $inserted++;
            }

            // DELETE: soft delete sejak $since
            if (Schema::hasColumn($tbl, 'deleted_at')) {
                $rowsDel = DB::table($tbl)->whereNotNull('deleted_at')->where('deleted_at','>', $since)->get();
                foreach ($rowsDel as $r) {
                    if (empty($r->uuid)) continue;
                    DB::table('sync_queue')->insert([
                        'operation_id' => (string) Str::uuid(),
                        'table'        => $tbl,
                        'op'           => 'delete',
                        'row'          => json_encode(['uuid'=>$r->uuid], JSON_UNESCAPED_UNICODE),
                        'created_at'   => now(),
                    ]);
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    // ---------------- PUSH: kirim ke server, hapus hanya yang sukses ----------------

    protected function pushQueue(string $baseUrl, string $deviceId, string $apiKey = ''): int
    {
        if (!Schema::hasTable('sync_queue')) return 0;

        $queued = DB::table('sync_queue')->orderBy('id')->get();
        if ($queued->count() === 0) return 0;

        $operations = [];
        foreach ($queued as $row) {
            $rowPayload = $row->row;
            if (is_string($rowPayload)) {
                $dec = json_decode($rowPayload, true);
                $rowPayload = is_array($dec) ? $dec : [];
            } elseif (!is_array($rowPayload)) {
                $rowPayload = [];
            }

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
        if ($apiKey !== '') $headers['X-Api-Key'] = $apiKey;

        $resp = Http::withHeaders($headers)->acceptJson()->retry(2, 250)->post($baseUrl . '/api/sync/push', ['operations' => $operations]);
        $resp->throw();

        $body = $resp->json() ?? [];
        $appliedOps = collect($body['applied'] ?? [])->filter(fn($it)=>!empty($it['operation_id']))->pluck('operation_id')->all();
        $rejected   = collect($body['rejected'] ?? []);
        if ($rejected->count() > 0) Log::warning('[Sync] push rejected', $rejected->toArray());

        if (!empty($appliedOps)) {
            DB::table('sync_queue')->whereIn('operation_id', $appliedOps)->delete();
        }

        return count($operations);
    }

    // ---------------- Utils ----------------

    protected function sanitizePayload(string $table, array $payload): array
    {
        unset($payload['id']);
        foreach (['created_at','updated_at','deleted_at','email_verified_at'] as $ts) {
            if (!empty($payload[$ts])) {
                try { $payload[$ts] = Carbon::parse($payload[$ts])->format('Y-m-d H:i:s'); } catch (Throwable $e) {}
            }
            if (array_key_exists($ts,$payload) && $payload[$ts] === '') $payload[$ts] = null;
        }

        if ($table === 'users') {
            if (empty($payload['password'])) $payload['password'] = Hash::make(Str::random(32));
            if (array_key_exists('remember_token', $payload) && is_null($payload['remember_token'])) unset($payload['remember_token']);
        }

        return $payload;
    }

    /** Hapus pasangan key=>null (0/false tetap) */
    protected function withoutNulls(array $data): array
    {
        return array_filter($data, static fn($v) => !is_null($v));
    }
}
