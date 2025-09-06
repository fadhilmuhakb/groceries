<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    $tables = [
  'daily_revenues',
'tb_brands',
'tb_customers',
'tb_incoming_goods',
'tb_outgoing_goods',
'tb_products',
'tb_purchases',
'tb_sells',
'tb_stock_opnames',
'tb_stores',
'tb_suppliers',
'tb_types',
'tb_units',
'users'
    ];
    foreach ($tables as $t) {
      Schema::table($t, function (Blueprint $table) {
        if (!Schema::hasColumn($table->getTable(), 'uuid')) {
          $table->uuid('uuid')->nullable()->unique()->after('id');
        }
        if (!Schema::hasColumn($table->getTable(), 'updated_at')) {
          $table->timestamps(); // kalau belum ada
        }
        if (!Schema::hasColumn($table->getTable(), 'deleted_at')) {
          $table->softDeletes();
        }
      });
    }
  }
  public function down(): void {
    // optional: drop kolom
  }
};
