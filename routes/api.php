<?php

use App\Http\Controllers\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\tb_daily_revenues; // Pastikan ini sesuai dengan nama model kamu
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/


Route::get('/sync/pull', [SyncController::class, 'pull']);
Route::post('/sync/push', [SyncController::class, 'push']);
