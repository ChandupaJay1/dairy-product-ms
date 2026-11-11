<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/generate-sku', [ProductController::class, 'getNextSku'])->name('products.generate-sku');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Category Management
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Stocks Management
    Route::get('/stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::post('/stocks', [StockController::class, 'store'])->name('stocks.store');
    Route::post('/stocks/receive', [StockController::class, 'store'])->name('stocks.receive');
    Route::post('/shipments', [StockController::class, 'store'])->name('shipments.store');
    Route::post('/stocks/{stock}/adjust', [StockController::class, 'adjust'])->name('stocks.adjust');
    Route::delete('/stocks/{stock}', [StockController::class, 'destroy'])->name('stocks.destroy');

    // Reports Management
    Route::get('/reports/inventory-pdf', [ReportController::class, 'exportInventoryPdf'])->name('reports.inventory-pdf');
});

Route::get('/distribution', function () {
    return view('distribution.hub', [
        'appTitle' => 'PulseERP — Polonnaruwa Dairy Fleet Operations',
    ]);
})->middleware('auth')->name('distribution.hub');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
