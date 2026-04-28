<?php

use App\Http\Controllers\Merchant\Product\ProductController as MerchantProductController;
use Illuminate\Support\Facades\Route;

// Merchant routes - requires merchant role
Route::middleware(['auth', 'role:merchant'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [MerchantProductController::class, 'dashboard'])->name('dashboard');

    // My Products
    Route::get('/products', [MerchantProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [MerchantProductController::class, 'create'])->name('products.create');
    Route::post('/products', [MerchantProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [MerchantProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [MerchantProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [MerchantProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [MerchantProductController::class, 'destroy'])->name('products.destroy');

    // Product status
    Route::get('/products/pending', [MerchantProductController::class, 'pending'])->name('products.pending');
    Route::get('/products/rejected', [MerchantProductController::class, 'rejected'])->name('products.rejected');
    Route::get('/products/approved', [MerchantProductController::class, 'approved'])->name('products.approved');
});