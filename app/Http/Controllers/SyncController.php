<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\SyncChange;
use App\Services\SyncService;

class SyncController extends Controller
{
  
    /** ============== FULL EXPORT (BARU) ============== 
     * GET /api/sync/export?tables=tb_products,tb_purchases&limit=5000&offset=0
     * - Mengembalikan SELURUH baris dari tabel yang diminta (bukan berdasarkan sync_changes)
     * - Paginated dengan limit+offset
     * - Hanya tabel yang memiliki kolom 'uuid'
     */
    public function export(Request $req)
    {
        $tables = array_values(array_filter(array_map('trim', explode(',', (string) $req->query('tables', '')))));
        $limit  = max(1, min((int) $req->query('limit', 5000), 20000));
        $offset = max(0, (int) $req->query('offset', 0));

        if (empty($tables)) {
            return response()->json(['error'=>true,'message'=>'tables parameter required'], 400);
        }

        $data = [];
        $total = 0;

        foreach ($tables as $tbl) {
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'uuid')) {
                $data[$tbl] = ['count'=>0, 'rows'=>[]];
                continue;
            }

            $rows = DB::table($tbl)
                ->orderBy('id')              // atau orderBy('uuid') kalau tidak ada id
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->map(fn($r)=>(array)$r)
                ->all();

            $data[$tbl] = ['count'=>count($rows), 'rows'=>$rows];
            $total += count($rows);
        }

        return response()->json([
            'tables'      => $tables,
            'limit'       => $limit,
            'offset'      => $offset,
            'next_offset' => $total > 0 ? $offset + $limit : null,
            'data'        => $data,
        ]);
    }

    /** ============== PULL BERDASARKAN CHANGE LOG (tetap) ============== */
    public function pull(Request $req)
    {
        $since  = $req->query('since');
        $cursor = (int) $req->query('cursor', 0);
        $tables = $req->query('tables');
        $limit  = min((int) $req->query('limit', 10000), 50000);

        $ts = $since ? Carbon::parse($since) : null;

        $changes = SyncChange::query()
            ->when($tables, fn($q) => $q->whereIn('table', array_map('trim', explode(',', $tables))))
            ->when($ts, function ($q) use ($ts, $cursor) {
                $q->where(fn($w) => $w
                    ->where('changed_at','>',$ts)
                    ->orWhere(fn($eq) => $eq->where('changed_at','=',$ts)->when($cursor>0, fn($e)=>$e->where('id','>',$cursor)))
                );
            })
            ->orderBy('changed_at')->orderBy('id')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($changes as $c) {
            $tbl = $c->table;
            $uuid = $c->row_uuid;
            $op = $c->action ?? 'upsert';

            $payload = [];
            if ($op !== 'delete' && Schema::hasTable($tbl) && Schema::hasColumn($tbl,'uuid')) {
                $row = DB::table($tbl)->where('uuid', $uuid)->first();
                if ($row) $payload = (array) $row;
            }
            $out[$tbl][] = [
                'op'       => $op,
                'row_uuid' => $uuid,
                'payload'  => $payload,
                'at'       => optional($c->changed_at)->toIso8601String(),
            ];
        }

        $last = $changes->last();

        return response()->json([
            'since'       => $since,
            'cursor'      => $cursor,
            'count'       => $changes->count(),
            'changes'     => $out,
            'next_since'  => optional($last)->changed_at?->toIso8601String(),
            'next_cursor' => optional($last)->id,
        ]);
    }

    /** ============== PUSH (tetap: Query Builder) ============== */
    public function push(Request $req)
    {
        $deviceId = $req->header('X-Device-Id');
        $ops = $req->input('operations', []);

        $applied = [];
        $rejected = [];

        DB::beginTransaction();
        try {
            foreach ($ops as $op) {
                $operationId = $op['operation_id'] ?? null;
                $table = $op['table'] ?? null;
                $row   = $op['row'] ?? [];
                $verb  = $op['op'] ?? 'upsert';

                if (!$operationId || !$table) {
                    $rejected[] = ['op'=>$op,'reason'=>'invalid_payload'];
                    continue;
                }

                // idempotensi
                if (DB::table('sync_operations')->where('operation_id',$operationId)->exists()) {
                    $applied[] = ['operation_id'=>$operationId,'status'=>'duplicate_ignored'];
                    continue;
                }

                if (!Schema::hasTable($table)) {
                    $rejected[] = ['op'=>$op,'reason'=>'unknown_table','table'=>$table];
                    continue;
                }
                if (!Schema::hasColumn($table,'uuid')) {
                    $rejected[] = ['op'=>$op,'reason'=>'missing_uuid_column','table'=>$table];
                    continue;
                }

                $uuid = $row['uuid'] ?? null;
                if (!$uuid) {
                    $rejected[] = ['op'=>$op,'reason'=>'missing_uuid','table'=>$table];
                    continue;
                }

                if ($verb === 'delete') {
                    if (Schema::hasColumn($table,'deleted_at')) DB::table($table)->where('uuid',$uuid)->update(['deleted_at'=>now()]);
                    else DB::table($table)->where('uuid',$uuid)->delete();

                    $this->appendChange($table, $uuid, 'delete');
                } else {
                    // sanitize sederhana
                    unset($row['id']);
                    foreach (['created_at','updated_at','deleted_at'] as $ts) {
                        if (!empty($row[$ts])) { try { $row[$ts]=Carbon::parse($row[$ts])->format('Y-m-d H:i:s'); } catch (\Throwable $e) {} }
                    }
                    // keep only columns in table
                    try {
                        $cols = Schema::getColumnListing($table);
                        $row = array_intersect_key($row, array_flip($cols));
                    } catch (\Throwable $e) {}

                    $row['uuid'] = $uuid;
                    DB::table($table)->updateOrInsert(['uuid'=>$uuid], $row);

                    $this->appendChange($table, $uuid, 'upsert');
                }

                DB::table('sync_operations')->insert([
                    'operation_id' => $operationId,
                    'table'        => $table,
                    'row_uuid'     => $uuid,
                    'device_id'    => $deviceId,
                    'applied_at'   => now(),
                    'result'       => json_encode(['status'=>'ok']),
                ]);

                $applied[] = ['operation_id'=>$operationId,'status'=>'ok'];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Sync][push] error: '.$e->getMessage());
            return response()->json(['error'=>true,'message'=>$e->getMessage()], 500);
        }

        if (!empty($rejected)) Log::warning('[Sync] push rejected', $rejected);
        return response()->json(['applied'=>$applied,'rejected'=>$rejected]);
    }

    protected function appendChange(string $tableName, string $rowUuid, string $action = 'upsert'): void
    {
        SyncChange::create([
            'table'      => $tableName,
            'row_uuid'   => $rowUuid,
            'action'     => $action,
            'changed_at' => now(),
        ]);
    }
    public function manual(SyncService $sync)
    {
        $sum = $sync->runFullResync();
        return back()->with('status', "Sinkronisasi selesai. Pulled: {$sum['pulled']}, Pushed: {$sum['pushed']}");
    }
}
