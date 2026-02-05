<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tb_outgoing_goods') || !Schema::hasColumn('tb_outgoing_goods', 'quantity_out')) {
            return;
        }

        DB::statement('ALTER TABLE tb_outgoing_goods MODIFY quantity_out BIGINT NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('tb_outgoing_goods') || !Schema::hasColumn('tb_outgoing_goods', 'quantity_out')) {
            return;
        }

        DB::statement('ALTER TABLE tb_outgoing_goods MODIFY quantity_out INT NOT NULL');
    }
};
