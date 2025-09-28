<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;
Route::middleware('throttle:sync')->group(function () {
    Route::get('/sync/export', [SyncController::class, 'export']);
    Route::get('/sync/pull',   [SyncController::class, 'pull']);
    Route::post('/sync/push',  [SyncController::class, 'push']);
});
