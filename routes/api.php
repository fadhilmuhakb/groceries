<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
