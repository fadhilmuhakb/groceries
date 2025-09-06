<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\SyncChange;
use App\Services\SyncService;
use Illuminate\Support\Facades\Artisan; 

class SyncController extends Controller
{
  // PULL: ambil perubahan sejak timestamp
  public function pull(Request $req) {
    $since = $req->query('since'); // ISO8601
    $tables = $req->query('tables'); // "tb_products,tb_suppliers" optional
    $limit = min((int) $req->query('limit', 1000), 5000);

    $q = SyncChange::query()
      ->when($since, fn($qq) => $qq->where('changed_at','>', Carbon::parse($since)))
      ->when($tables, function($qq) use ($tables) {
        $arr = array_map('trim', explode(',', $tables));
        $qq->whereIn('table', $arr);
      })
      ->orderBy('changed_at')->limit($limit)
      ->get();

    // Kelompokkan per table untuk klien
    $grouped = $q->groupBy('table')->map->values();

    return response()->json([
      'since' => $since,
      'count' => $q->count(),
      'changes' => $grouped, // { tb_products: [ ... ], tb_sales: [ ... ] }
      'next_since' => optional($q->last())->changed_at?->toIso8601String(),
    ]);
  }

  // PUSH: klien kirim operasi
  public function push(Request $req) {
    $deviceId = $req->header('X-Device-Id');
    $ops = $req->input('operations', []); // [{operation_id, table, row, op}, ...]

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

        // tentukan model dari nama tabel
        $modelClass = \Illuminate\Support\Str::of($table)->studly()->prepend('App\\Models\\')->__toString();
        if (!class_exists($modelClass)) {
          $rejected[] = ['op'=>$op,'reason'=>'unknown_table'];
          continue;
        }

        // upsert/delete by uuid
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
        } else {
          // untuk transaksi, lebih aman insert dokumen baru (append-only)
          // untuk master: upsert by uuid
          if ($current) {
            // LWW: cek updated_at
            if (!empty($row['updated_at']) && $current->updated_at >= $row['updated_at']) {
              // server lebih baru → skip
            } else {
              $current->fill($row);
              $current->save();
            }
          } else {
            $m = $model->fill($row);
            $m->save();
          }
        }

        DB::table('sync_operations')->insert([
          'operation_id' => $operationId,
          'table'        => $table,
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
  public function syncNow() {
    \Artisan::call('sync:run');
    return back()->with('status', 'Sinkronisasi selesai!');
}
public function manual(SyncService $sync) {
        $sync->run();
        return back()->with('status', 'Sinkronisasi berhasil dijalankan!');
    }
}
