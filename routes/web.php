<?php

use App\Http\Controllers\TbBrandsController;
use App\Http\Controllers\TbIncomingGoodsController;
use App\Http\Controllers\TbProductsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\TbStoresController;
use App\Http\Controllers\TbSuppliersController;
use App\Http\Controllers\TbTypesController;
use App\Http\Controllers\TbUnitsController;
use App\Http\Controllers\TbUserController;
use App\Http\Controllers\TbPurchaseController;
use App\Http\Controllers\TbSalesController;
use App\Models\User;
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


Route::group(['middleware' => ['auth']], function () {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


    Route::prefix('master-type')->group(function () {
        Route::get('/', [TbTypesController::class, 'index'])->name('master-types.index');
        Route::get('/create', [TbTypesController::class, 'create'])->name('master-types.create');
        Route::get('/edit/{id}', [TbTypesController::class, 'edit'])->name('master-types.edit');
        Route::post('/store', [TbTypesController::class, 'store'])->name('master-type.store');
        Route::put('/update/{id}', [TbTypesController::class, 'update'])->name('master-type.update');
        Route::delete('/delete/{id}', [TbTypesController::class, 'destroy'])->name('master-type.delete');

    });
    Route::prefix('master-brand')->group(function () {
        Route::get('/', [TbBrandsController::class, 'index'])->name('master-brand.index');
        Route::get('/create', [TbBrandsController::class, 'create'])->name('master-brand.create');
        Route::get('/edit/{id}', [TbBrandsController::class, 'edit'])->name('master-brand.edit');
        Route::post('/store', [TbBrandsController::class, 'store'])->name('master-brand.store');
        Route::put('/update/{id}', [TbBrandsController::class, 'update'])->name('master-brand.update');
        Route::delete('/delete/{id}', [TbBrandsController::class, 'destroy'])->name('master-brand.delete');

    });
    Route::prefix('master-unit')->group(callback: function () {
        Route::get('/', [TbUnitsController::class, 'index'])->name('master-unit.index');
        Route::get('/create', [TbUnitsController::class, 'create'])->name('master-unit.create');
        Route::get('/edit/{id}', [TbUnitsController::class, 'edit'])->name('master-unit.edit');
        Route::post('/store', [TbUnitsController::class, 'store'])->name('master-unit.store');
        Route::put('/update/{id}', [TbUnitsController::class, 'update'])->name('master-unit.update');
        Route::delete('/delete/{id}', [TbUnitsController::class, 'destroy'])->name('master-unit.delete');
    });
    Route::prefix('master-product')->group(function () {
        Route::get('/', [TbProductsController::class, 'index'])->name('master-product.index');
        Route::get('/create', [TbProductsController::class, 'create'])->name('master-product.create');
        Route::get('/{id}', [TbProductsController::class, 'show'])->name('master-product.show');
        Route::get('/edit/{id}', [TbProductsController::class, 'edit'])->name('master-product.edit');
        Route::post('/store', [TbProductsController::class, 'store'])->name('master-product.store');
        Route::put('/update/{id}', [TbProductsController::class, 'update'])->name('master-product.update');
        Route::delete('/delete/{id}', [TbProductsController::class, 'destroy'])->name('master-product.delete');
        Route::get('/import', [TbProductsController::class, 'import'])->name('master-product.import');
        Route::post('/import', [TbProductsController::class, 'import'])->name('master-product.import');
        Route::get('/preview', [TbProductsController::class, 'preview'])->name('master-product.preview');
        Route::post('/save-imported', [TbProductsController::class, 'saveImported'])->name('master-product.saveImported');
    });

    Route::prefix('user')->group(function () {
        Route::get('/', [TbUserController::class, 'index'])->name('user.index');
        Route::get('/create', [TbUserController::class, 'create'])->name('user.create');
        Route::get('/edit/{id}', [TbUserController::class, 'edit'])->name('user.edit');
        Route::post('/store', [TbUserController::class, 'store'])->name('user.store');
        Route::put('/update/{id}', [TbUserController::class, 'update'])->name('user.update');
        Route::put('/update/password/{id}', [TbUserController::class, 'updatePassword'])->name('user.update.password');
        Route::delete('/delete/{id}', [TbUserController::class, 'destroy'])->name('user.delete');
    });

    Route::prefix('supplier')->group(function () {
        Route::get('/', [TbSuppliersController::class, 'index'])->name('supplier.index');
        Route::get('/create', [TbSuppliersController::class, 'create'])->name('supplier.create');
        Route::get('/edit/{id}', [TbSuppliersController::class, 'edit'])->name('supplier.edit');
        Route::post('/store', [TbSuppliersController::class, 'store'])->name('supplier.store');
        Route::put('/update/{id}', [TbSuppliersController::class, 'update'])->name('supplier.update');
        Route::delete('/delete/{id}', [TbSuppliersController::class, 'destroy'])->name('supplier.delete');
    });

    Route::prefix('purchase')->group(function () {
        Route::get('/', [TbPurchaseController::class, 'index'])->name('purchase.index');
        Route::get('/create', [TbPurchaseController::class, 'create'])->name('purchase.create');
        Route::get('/edit/{id}', [TbPurchaseController::class, 'edit'])->name('purchase.edit');
        Route::get('/store', [TbPurchaseController::class, 'store'])->name('purchase.store');
        Route::post('/store', [TbPurchaseController::class, 'store'])->name('purchase.store'); // Pastikan ini ada
        Route::get('/update/{id}', [TbPurchaseController::class, 'update'])->name('purchase.update');
        Route::put('/update/{id}', [TbPurchaseController::class, 'update'])->name('purchase.update');
        Route::get('/delete/{id}', [TbPurchaseController::class, 'delete'])->name('purchase.delete');
    });
    Route::prefix('store')->group(function () {
        Route::get('/', [TbStoresController::class, 'index'])->name('store.index');
        Route::get('/create', [TbStoresController::class, 'create'])->name('store.create');
        Route::get('/edit/{id}', [TbStoresController::class, 'edit'])->name('store.edit');
        Route::post('/store', [TbStoresController::class, 'store'])->name('store.store');
        Route::put('/update/{id}', [TbStoresController::class, 'update'])->name('store.update');
        Route::delete('/delete/{id}', [TbStoresController::class, 'delete'])->name('store.delete');
    });

    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
    });


    Route::prefix('sales')->group(function() {
        Route::get('/', [TbSalesController::class, 'index'])->name('sales.index');
    });

});
