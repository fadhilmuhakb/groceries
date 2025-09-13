<?php

use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::get('/sync/pull',   [SyncController::class, 'pull']);    // tetap ada (berbasis change log)
Route::post('/sync/push',  [SyncController::class, 'push']);    // tetap ada (untuk menerima upsert/delete)
Route::get('/sync/export', [SyncController::class, 'export']);  // ⬅️ BARU: full dump per tabel, paginated
