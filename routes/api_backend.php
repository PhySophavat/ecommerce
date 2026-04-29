<?php

use App\Http\Controllers\Api\Backend\Product\ProductController;
use App\Http\Controllers\Api\Backend\Slide\SlideController;
use App\Http\Controllers\Api\Backend\Slide\SlideDashboardController;
use App\Http\Controllers\Api\Backend\AuthController;
use Illuminate\Support\Facades\Route;

// Public API routes for authentication
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected API routes
Route::middleware(['auth', 'role:admin', 'admin.otp'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/slides/dashboard', [SlideDashboardController::class, 'index'])->name('slides.dashboard');
    Route::post('/slides', [SlideController::class, 'store'])->name('slides.store');
    Route::put('/slides/{slide}', [SlideController::class, 'update'])->name('slides.update');
    Route::delete('/slides/{slide}', [SlideController::class, 'destroy'])->name('slides.destroy');
});
