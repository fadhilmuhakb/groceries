<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    if (!Schema::hasTable('sync_meta')) {
      Schema::create('sync_meta', function (Blueprint $t) {
        $t->id();
        $t->string('last_successful_sync')->nullable(); // simpan ISO8601
        $t->timestamps();
      });
    } else if (!Schema::hasColumn('sync_meta','last_successful_sync')) {
      Schema::table('sync_meta', function (Blueprint $t) {
        $t->string('last_successful_sync')->nullable()->after('id');
      });
    }
  }
  public function down(): void {
    // optional drop column
  }
};
