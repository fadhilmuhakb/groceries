<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SyncChange;
use App\Services\SyncService;

class SyncController extends Controller
{
    // SERVER → CLIENT: PULL (kirim payload & cursor)
    public function pull(Request $req)
    {
        $since  = $req->query('since');             // ISO8601
        $cursor = (int) $req->query('cursor', 0);   // tie-breaker id
        $tables = $req->query('tables');            // "tb_products,tb_suppliers"
        $limit  = min((int) $req->query('limit', 10000), 50000);

        $ts = $since ? Carbon::parse($since) : null;

        $changes = SyncChange::query()
            ->when($tables, function ($q) use ($tables) {
                $arr = array_map('trim', explode(',', $tables));
                $q->whereIn('table', $arr); // ganti 'table' → 'table_name' bila perlu
            })
            ->when($ts, function ($q) use ($ts, $cursor) {
                $q->where(function ($w) use ($ts, $cursor) {
                    $w->where('changed_at', '>', $ts)
                      ->orWhere(function ($eq) use ($ts, $cursor) {
                          $eq->where('changed_at', '=', $ts);
                          if ($cursor > 0) $eq->where('id', '>', $cursor);
                      });
                });
            })
            ->orderBy('changed_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($changes as $c) {
            $tbl  = $c->table; // atau $c->table_name
            $uuid = $c->row_uuid;
            $op   = $c->action ?? 'upsert';

            $payload = [];
            if ($op !== 'delete' && \Schema::hasTable($tbl) && \Schema::hasColumn($tbl, 'uuid')) {
                $row = \DB::table($tbl)->where('uuid', $uuid)->first();
                if ($row) $payload = (array) $row;
            }

            $out[$tbl][] = [
                'op'       => $op,
                'row_uuid' => $uuid,
                'payload'  => $payload,
                'at'       => $c->changed_at?->toIso8601String(),
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

    // CLIENT → SERVER: PUSH (catat ke tabel domain + stream sync_changes)
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
                $verb  = $op['op'] ?? 'upsert'; // upsert|delete

                if (!$operationId || !$table) {
                    $rejected[] = ['op'=>$op,'reason'=>'invalid_payload'];
                    continue;
                }

                // idempotensi
                $exists = DB::table('sync_operations')->where('operation_id',$operationId)->exists();
                if ($exists) {
                    $applied[] = ['operation_id'=>$operationId,'status'=>'duplicate_ignored'];
                    continue;
                }

                // MAP tabel → model (ISI sesuai projekmu jika model beda nama)
                $map = [
                    'tb_outgoing_goods'  => \App\Models\TbOutgoingGoods::class,
                    'tb_incoming_goods'  => \App\Models\TbIncomingGoods::class,
                    'tb_products'        => \App\Models\TbProducts::class,
                    'tb_suppliers'       => \App\Models\TbSuppliers::class,
                    'tb_customers'       => \App\Models\TbCustomers::class,
                    'tb_sells'           => \App\Models\TbSells::class,
                    'tb_purchases'       => \App\Models\TbPurchases::class,
                    'tb_stock_opnames'   => \App\Models\TbStockOpnames::class,
                    'tb_types'           => \App\Models\TbTypes::class,
                    'tb_units'           => \App\Models\TbUnits::class,
                    'tb_brands'          => \App\Models\TbBrands::class,
                    'tb_stores'          => \App\Models\TbStores::class,
                    'daily_revenues'     => \App\Models\DailyRevenue::class,
                    // tambahkan lainnya jika nama modelnya tidak sama dengan StudlyCase tabel
                ];
                $modelClass = $map[$table] ?? \Illuminate\Support\Str::of($table)->studly()->prepend('App\\Models\\')->__toString();
                if (!class_exists($modelClass)) {
                    $rejected[] = ['op'=>$op,'reason'=>'unknown_table'];
                    continue;
                }

                /** @var \Illuminate\Database\Eloquent\Model $model */
                $model = new $modelClass;
                $query = $model->newQuery();

                if (empty($row['uuid'])) {
                    $rejected[] = ['op'=>$op,'reason'=>'missing_uuid'];
                    continue;
                }

                $current = $query->where('uuid',$row['uuid'])->first();

                if ($verb === 'delete') {
                    if ($current) $current->delete();
                    $this->appendChange($table, $row['uuid'], 'delete');
                } else {
                    if ($current) {
                        $incomingAt = !empty($row['updated_at']) ? Carbon::parse($row['updated_at']) : null;
                        if ($incomingAt && $current->updated_at && $current->updated_at->greaterThanOrEqualTo($incomingAt)) {
                            // server lebih baru → skip
                        } else {
                            $current->fill($row);
                            $current->save();
                            $this->appendChange($table, $row['uuid'], 'upsert');
                        }
                    } else {
                        $m = $model->fill($row);
                        $m->save();
                        $this->appendChange($table, $row['uuid'], 'upsert');
                    }
                }

                DB::table('sync_operations')->insert([
                    'operation_id' => $operationId,
                    'table'        => $table, // ganti ke 'table_name' jika skema kamu
                    'row_uuid'     => $row['uuid'],
                    'device_id'    => $deviceId,
                    'applied_at'   => now(),
                    'result'       => json_encode(['status'=>'ok'])
                ]);

                $applied[] = ['operation_id'=>$operationId,'status'=>'ok'];
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error'=>true,'message'=>$e->getMessage()], 500);
        }

        return response()->json(['applied'=>$applied,'rejected'=>$rejected]);
    }

    // Tombol manual di sidebar → jalankan PUSH→PULL, lalu balik
    public function manual(SyncService $sync)
    {
        $sync->run();
        return back()->with('status', 'Sinkronisasi berhasil dijalankan!');
    }

    // helper: catat ke stream perubahan
    protected function appendChange(string $tableName, string $rowUuid, string $action = 'upsert'): void
    {
        SyncChange::create([
            'table'      => $tableName,  // kalau kolommu 'table_name', ubah key ini
            'row_uuid'   => $rowUuid,
            'action'     => $action,     // 'upsert'|'delete'
            'changed_at' => now(),
        ]);
    }
}
