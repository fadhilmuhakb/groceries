<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('menus', function (Blueprint $t) {
      $t->id();
      $t->string('title');
      $t->string('route')->nullable();  // e.g. 'reports.laba.index' (nama route)
      $t->string('icon')->nullable();
      $t->unsignedBigInteger('parent_id')->nullable()->index();
      $t->integer('order')->default(0);
      // simpan array permission name (Spatie) yang disyaratkan menu ini (AND di level item)
      $t->json('required_permissions')->nullable(); // ['report.laba.view']
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('menus'); }
};
