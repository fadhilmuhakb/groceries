<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncPendingStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync-pending {store_id? : Optional store ID, otherwise all online stores}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark pending stock movements (incoming/outgoing) as synced for online stores so they cut stock';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetStore = $this->argument('store_id');
        $now = now();

        $stores = DB::table('tb_stores')
            ->when($targetStore, fn($q) => $q->where('id', $targetStore))
            ->where('is_online', 1)
            ->pluck('id');

        if ($stores->isEmpty()) {
            $this->info('No online stores to sync.');
            return self::SUCCESS;
        }

        foreach ($stores as $sid) {
            $updatedIn = DB::table('tb_incoming_goods as ig')
                ->join('tb_purchases as p', 'p.id', '=', 'ig.purchase_id')
                ->where('p.store_id', $sid)
                ->where('ig.is_pending_stock', 1)
                ->when(
                    Schema::hasColumn('tb_incoming_goods', 'deleted_at'),
                    fn ($q) => $q->whereNull('ig.deleted_at')
                )
                ->update([
                    'ig.is_pending_stock' => 0,
                    'ig.synced_at'        => $now,
                    'ig.updated_at'       => $now,
                ]);

            $updatedOut = DB::table('tb_outgoing_goods as og')
                ->join('tb_sells as s', 's.id', '=', 'og.sell_id')
                ->where('s.store_id', $sid)
                ->where('og.is_pending_stock', 1)
                ->when(
                    Schema::hasColumn('tb_outgoing_goods', 'deleted_at'),
                    fn ($q) => $q->whereNull('og.deleted_at')
                )
                ->update([
                    'og.is_pending_stock' => 0,
                    'og.synced_at'        => $now,
                    'og.updated_at'       => $now,
                ]);

            $this->info("Store {$sid}: synced incoming={$updatedIn}, outgoing={$updatedOut}");
        }

        return self::SUCCESS;
    }
}
