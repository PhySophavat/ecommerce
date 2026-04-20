<?php

use App\Http\Controllers\Api\Backend\ProductDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductDashboardController::class, 'index'])->name('products.index');
Route::post('/products', [ProductDashboardController::class, 'store'])->name('products.store');
