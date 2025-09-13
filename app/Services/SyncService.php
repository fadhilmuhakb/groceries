<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SyncService
{
    protected array $denyTables = [
        'sync_changes','sync_meta','sync_operations','sync_queue',
        'migrations','failed_jobs','jobs','job_batches',
    ];

    /** FULL RESYNC: 1) export full → apply  2) enqueue semua lokal  3) push ke server */
    public function runFullResync(): array
    {
        $tables   = $this->tablesFromEnv();

        $pulled   = $this->importAllFromServer($tables);
        $enqueued = $this->enqueueAllLocalRows($tables);

        $baseUrl  = rtrim((string) config('sync.base_url', ''), '/');
        $deviceId = (string) config('sync.device_id', 'offline-device-001');
        $apiKey   = (string) config('sync.api_key', '');
        $pushed   = $this->pushQueue($baseUrl, $deviceId, $apiKey);

        Log::info('[Sync][full] summary', compact('pulled','enqueued','pushed'));
        return ['pulled'=>$pulled,'enqueued'=>$enqueued,'pushed'=>$pushed];
    }

    /** Import semua baris via /api/sync/export (paginated) lalu apply ke DB lokal (upsert by uuid) */
    public function importAllFromServer(array $tables, int $pageSize = 5000): int
    {
        if (empty($tables)) return 0;

        $baseUrl = rtrim((string) config('sync.base_url', ''), '/');
        if ($baseUrl === '') throw new \RuntimeException('SYNC_BASE_URL belum diset');
        $deviceId = (string) config('sync.device_id', 'offline-device-001');
        $apiKey   = (string) config('sync.api_key', '');

        $headers = ['X-Device-Id'=>$deviceId] + ($apiKey ? ['X-Api-Key'=>$apiKey] : []);
        $offset  = 0;
        $totalApplied = 0;

        do {
            $resp = Http::withHeaders($headers)->acceptJson()->retry(2, 250)->get($baseUrl.'/api/sync/export', [
                'tables' => implode(',', $tables),
                'limit'  => $pageSize,
                'offset' => $offset,
            ]);
            $resp->throw();

            $body = $resp->json() ?? [];
            $data = Arr::get($body, 'data', []);
            $next = $body['next_offset'] ?? null;

            DB::transaction(function () use ($data, &$totalApplied) {
                foreach ($data as $table => $payload) {
                    $rows = $payload['rows'] ?? [];
                    if (!Schema::hasTable($table) || !Schema::hasColumn($table,'uuid')) continue;

                    foreach ($rows as $r) {
                        $row = (array) $r;
                        if (empty($row['uuid'])) continue;

                        unset($row['id']);
                        foreach (['created_at','updated_at','deleted_at'] as $ts) {
                            if (!empty($row[$ts])) {
                                try { $row[$ts] = Carbon::parse($row[$ts])->format('Y-m-d H:i:s'); } catch (\Throwable $e) {}
                            }
                        }
                        DB::table($table)->updateOrInsert(['uuid'=>$row['uuid']], $row);
                        $totalApplied++;
                    }
                }
            });

            $offset = $next ?? null;
        } while ($offset !== null);

        return $totalApplied;
    }

    protected function tablesFromEnv(): array
    {
        $cfg = trim((string) config('sync.tables',''));
        $arr = array_values(array_filter(array_map('trim', explode(',', $cfg))));
        return array_values(array_filter($arr, fn($t)=>$t!=='' && !in_array($t,$this->denyTables,true)));
    }

    /** Enqueue seluruh baris lokal dari tabel domain (tanpa cek timestamp) */
    public function enqueueAllLocalRows(array $tables): int
    {
        if (!Schema::hasTable('sync_queue')) return 0;

        $count = 0;
        foreach ($tables as $tbl) {
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl,'uuid')) continue;
            $rows = DB::table($tbl)->get();
            foreach ($rows as $r) {
                if (empty($r->uuid)) continue;
                DB::table('sync_queue')->insert([
                    'operation_id' => (string) Str::uuid(),
                    'table'        => $tbl,
                    'op'           => 'upsert',
                    'row'          => json_encode((array)$r, JSON_UNESCAPED_UNICODE),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $count++;
            }
        }
        return $count;
    }

    /** Kirim queue ke server; hapus hanya yang “applied” */
    protected function pushQueue(string $baseUrl, string $deviceId, string $apiKey = ''): int
    {
        if (!Schema::hasTable('sync_queue')) return 0;
        $queued = DB::table('sync_queue')->orderBy('id')->get();
        if ($queued->count() === 0) return 0;

        $ops = [];
        foreach ($queued as $row) {
            $payload = $row->row;
            if (is_string($payload)) { $dec=json_decode($payload,true); $payload=is_array($dec)?$dec:[]; }
            elseif (!is_array($payload)) { $payload=[]; }
            $ops[] = ['operation_id'=>$row->operation_id,'table'=>$row->table,'op'=>$row->op,'row'=>$payload];
        }

        $headers = ['X-Device-Id'=>$deviceId] + ($apiKey ? ['X-Api-Key'=>$apiKey] : []);
        $resp = Http::withHeaders($headers)->acceptJson()->retry(2, 250)->post($baseUrl.'/api/sync/push', ['operations'=>$ops]);
        $resp->throw();

        $body = $resp->json() ?? [];
        $appliedOps = collect($body['applied'] ?? [])->pluck('operation_id')->filter()->all();
        if (!empty($appliedOps)) DB::table('sync_queue')->whereIn('operation_id',$appliedOps)->delete();

        $rejected = $body['rejected'] ?? [];
        if (!empty($rejected)) Log::warning('[Sync] push rejected', $rejected);

        return count($ops);
    }
}
