<?php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncMetaSeeder extends Seeder {
    public function run(): void {
        DB::table('sync_meta')->updateOrInsert(
            ['key' => 'last_successful_sync'],
            ['value' => '1970-01-01T00:00:00Z', 'updated_at'=>now(), 'created_at'=>now()]
        );
    }
}
