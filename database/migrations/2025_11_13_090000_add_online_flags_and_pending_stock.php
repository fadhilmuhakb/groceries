<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tb_stores', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_stores', 'is_online')) {
                $table->boolean('is_online')->default(true)->after('store_name');
            }
            if (!Schema::hasColumn('tb_stores', 'offline_since')) {
                $table->timestamp('offline_since')->nullable()->after('is_online');
            }
            if (!Schema::hasColumn('tb_stores', 'offline_note')) {
                $table->text('offline_note')->nullable()->after('offline_since');
            }
        });

        foreach (['tb_outgoing_goods', 'tb_incoming_goods'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'is_pending_stock')) {
                    $table->boolean('is_pending_stock')->default(false)->after('description');
                }
                if (!Schema::hasColumn($table->getTable(), 'synced_at')) {
                    $table->timestamp('synced_at')->nullable()->after('is_pending_stock');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_stores', function (Blueprint $table) {
            if (Schema::hasColumn('tb_stores', 'offline_note')) {
                $table->dropColumn('offline_note');
            }
            if (Schema::hasColumn('tb_stores', 'offline_since')) {
                $table->dropColumn('offline_since');
            }
            if (Schema::hasColumn('tb_stores', 'is_online')) {
                $table->dropColumn('is_online');
            }
        });

        foreach (['tb_outgoing_goods', 'tb_incoming_goods'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'synced_at')) {
                    $table->dropColumn('synced_at');
                }
                if (Schema::hasColumn($table->getTable(), 'is_pending_stock')) {
                    $table->dropColumn('is_pending_stock');
                }
            });
        }
    }
};
