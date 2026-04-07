<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return redirect()->route('login');
});

// Habilitar registro público
Auth::routes(['register' => true]);

// Rutas adicionales de autenticación


// Google OAuth
Route::get('auth/google', [SocialiteController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);

// Products Routes con throttling
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::post('/products/update-names', [ProductController::class, 'updateNames'])->name('products.update-names');
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/export-verified', [ProductController::class, 'exportVerified'])->name('products.export-verified');

    // Rutas para exportar/importar página actual
    Route::post('/products/export-current-page', [ProductController::class, 'exportCurrentPage'])->name('products.export-current-page');
    Route::post('/products/import-current-page', [ProductController::class, 'importCurrentPage'])->name('products.import-current-page');

    // Rutas de actualización de stock con throttling más permisivo
    Route::middleware(['throttle:120,1'])->group(function () {
        Route::post('/products/{product}/update-stock', [ProductController::class, 'updateStock'])->name('products.updateStock');
        Route::post('/products/{product}/update-stock-2', [ProductController::class, 'updateStock2'])->name('products.updateStock2');
        Route::post('/products/{product}/update-stock-3', [ProductController::class, 'updateStock3'])->name('products.updateStock3');
    });

    Route::get('/products/{product}/history-data', [ProductController::class, 'historyData'])->name('products.historyData');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{product}/history', [ProductController::class, 'history'])->name('products.history');
});

Route::get('/home', function() {
    return redirect()->route('products.index');
})->name('home');

// Rutas de Patrimonio
Route::middleware(['auth'])->group(function () {
    Route::get('/patrimonio', [App\Http\Controllers\PatrimonioController::class, 'index'])->name('patrimonio.index');
    Route::post('/patrimonio', [App\Http\Controllers\PatrimonioController::class, 'store'])->name('patrimonio.store');
    Route::put('/patrimonio/{item}', [App\Http\Controllers\PatrimonioController::class, 'update'])->name('patrimonio.update');
    Route::delete('/patrimonio/{item}', [App\Http\Controllers\PatrimonioController::class, 'destroy'])->name('patrimonio.destroy');
    Route::get('/patrimonio/{piso}/{area}', [App\Http\Controllers\PatrimonioController::class, 'getByArea'])->name('patrimonio.getByArea');
    Route::get('/patrimonio/{piso}/{area}/summary', [App\Http\Controllers\PatrimonioController::class, 'getAreaSummary'])->name('patrimonio.getAreaSummary');
    Route::post('/patrimonio/{piso}/{area}/close', [App\Http\Controllers\PatrimonioController::class, 'closeInventory'])->name('patrimonio.closeInventory');
});

// Rutas para Descuentos (aside)
Route::middleware(['auth'])->group(function () {
    Route::get('/discounts', [App\Http\Controllers\DiscountsController::class, 'index'])->name('discounts.index');
    Route::post('/discounts/import-products', [App\Http\Controllers\DiscountsController::class, 'importProducts'])->name('discounts.import-products');
    Route::post('/discounts/update-prices', [App\Http\Controllers\DiscountsController::class, 'updatePrices'])->name('discounts.update-prices');
    Route::post('/discounts/delete-all', [App\Http\Controllers\DiscountsController::class, 'deleteAll'])->name('discounts.delete-all');
    Route::post('/discounts/{producto}/update-fecha', [App\Http\Controllers\DiscountsController::class, 'updateFecha'])->name('discounts.update-fecha');
});

// Rutas de mantenimiento
Route::middleware(['auth'])->group(function () {
    Route::get('/maintenance', [App\Http\Controllers\MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance/fix-encoding', [App\Http\Controllers\MaintenanceController::class, 'fixEncoding'])->name('maintenance.fix-encoding');
    Route::post('/maintenance/clear-cache', [App\Http\Controllers\MaintenanceController::class, 'clearCache'])->name('maintenance.clear-cache');
});
