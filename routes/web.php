<?php

use App\Http\Controllers\TbTypesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('login');
// });

Auth::routes();


Route::group(['middleware' => ['auth']], function() {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


    Route::prefix('master-type')->group(function() {
        Route::get('/', [TbTypesController::class, 'index'])->name('master-types.index');
        Route::get('/create', [TbTypesController::class, 'create'])->name('master-types.create');
        Route::get('/edit/{id}', [TbTypesController::class, 'edit'])->name('master-types.edit');
        Route::post('/store', [TbTypesController::class, 'store'])->name('master-type.store');
        Route::put('/update/{id}', [TbTypesController::class, 'update'])->name('master-type.update');
        Route::delete('/delete/{id}', [TbTypesController::class, 'destroy'])->name('master-type.delete');
        
    });

});
