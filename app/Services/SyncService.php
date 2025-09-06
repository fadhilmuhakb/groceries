<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class SyncService {
    public function run() {
        $baseUrl = config('sync.base_url');   // http://server-domain
        $deviceId = config('sync.device_id'); // unik per device
        $since = DB::table('sync_meta')->value('last_successful_sync') ?? '1970-01-01T00:00:00Z';

        // 1. Push antrian lokal
        $ops = DB::table('sync_queue')->get();
        if ($ops->count()) {
            Http::withHeaders(['X-Device-Id' => $deviceId])
                ->post("$baseUrl/api/sync/push", ['operations' => $ops]);
            DB::table('sync_queue')->delete();
        }

        // 2. Pull perubahan
        $res = Http::withHeaders(['X-Device-Id' => $deviceId])
            ->get("$baseUrl/api/sync/pull", ['since' => $since, 'limit' => 1000])
            ->json();

        foreach ($res['changes'] as $table => $changes) {
            foreach ($changes as $c) {
                if ($c['op'] === 'upsert') {
                    DB::table($table)->updateOrInsert(['uuid'=>$c['row_uuid']], $c['payload']);
                } elseif ($c['op'] === 'delete') {
                    DB::table($table)->where('uuid',$c['row_uuid'])->update(['deleted_at'=>now()]);
                }
            }
        }

        if (!empty($res['next_since'])) {
            DB::table('sync_meta')->updateOrInsert(
                ['key'=>'last_successful_sync'], ['value'=>$res['next_since']]
            );
        }
    }
}
