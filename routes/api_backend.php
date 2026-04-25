<?php

use App\Http\Controllers\Api\Backend\ProductDashboardController;
use App\Http\Controllers\Api\Backend\SlideController;
use App\Http\Controllers\Api\Backend\SlideDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductDashboardController::class, 'index'])->name('products.index');
Route::post('/products', [ProductDashboardController::class, 'store'])->name('products.store');
Route::get('/products/{product}', [ProductDashboardController::class, 'show'])->name('products.show');
Route::put('/products/{product}', [ProductDashboardController::class, 'update'])->name('products.update');
Route::delete('/products/{product}', [ProductDashboardController::class, 'destroy'])->name('products.destroy');
Route::get('/slides/dashboard', [SlideDashboardController::class, 'index'])->name('slides.dashboard');
Route::post('/slides', [SlideController::class, 'store'])->name('slides.store');
Route::put('/slides/{slide}', [SlideController::class, 'update'])->name('slides.update');
Route::delete('/slides/{slide}', [SlideController::class, 'destroy'])->name('slides.destroy');
