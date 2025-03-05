<?php

use App\Http\Controllers\TbBrandsController;
use App\Http\Controllers\TbProductsController;
use App\Http\Controllers\TbTypesController;
use App\Http\Controllers\TbUnitsController;
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
    Route::prefix('master-brand')->group(function() {
        Route::get('/', [TbBrandsController::class, 'index'])->name('master-brand.index');
        Route::get('/create', [TbBrandsController::class, 'create'])->name('master-brand.create');
        Route::get('/edit/{id}', [TbBrandsController::class, 'edit'])->name('master-brand.edit');
        Route::post('/store', [TbBrandsController::class, 'store'])->name('master-brand.store');
        Route::put('/update/{id}', [TbBrandsController::class, 'update'])->name('master-brand.update');
        Route::delete('/delete/{id}', [TbBrandsController::class, 'destroy'])->name('master-brand.delete');

    });
    Route::prefix('master-unit')->group(callback: function() {
        Route::get('/', [TbUnitsController::class, 'index'])->name('master-unit.index');
        Route::get('/create', [TbUnitsController::class, 'create'])->name('master-unit.create');
        Route::get('/edit/{id}', [TbUnitsController::class, 'edit'])->name('master-unit.edit');
        Route::post('/store', [TbUnitsController::class, 'store'])->name('master-unit.store');
        Route::put('/update/{id}', [TbUnitsController::class, 'update'])->name('master-unit.update');
        Route::delete('/delete/{id}', [TbUnitsController::class, 'destroy'])->name('master-unit.delete');
    });
    Route::prefix('master-product')->group(callback: function() {
        Route::get('/', [TbProductsController::class, 'index'])->name('master-product.index');
        Route::get('/create', [TbProductsController::class, 'create'])->name('master-product.create');
        Route::get('/edit/{id}', [TbProductsController::class, 'edit'])->name('master-product.edit');
        Route::post('/store', [TbProductsController::class, 'store'])->name('master-product.store');
        Route::put('/update/{id}', [TbProductsController::class, 'update'])->name('master-product.update');
        Route::delete('/delete/{id}', [TbProductsController::class, 'destroy'])->name('master-product.delete');
    });
});
