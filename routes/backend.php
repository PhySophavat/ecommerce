<?php

use App\Http\Controllers\Backend\Product\ProductController;
use App\Http\Controllers\Backend\Product\ProductApprovalController;
use App\Http\Controllers\Backend\Slide\SlideController;
use App\Http\Controllers\Backend\User\UserController;
use Illuminate\Support\Facades\Route;

// Admin routes - requires admin role
Route::middleware(['auth', 'role:admin', 'admin.otp'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [ProductController::class, 'dashboard'])->name('dashboard');

    // Sliders
    Route::get('/sliders', [SlideController::class, 'index'])->name('sliders.index');

    // Products management (all products)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/featured', [ProductController::class, 'featured'])->name('products.featured');

    // Product approval/rejection
    Route::get('/products/pending', [ProductApprovalController::class, 'pending'])->name('products.pending');
    Route::post('/products/{product}/approve', [ProductApprovalController::class, 'approve'])->name('products.approve');
    Route::post('/products/{product}/reject', [ProductApprovalController::class, 'reject'])->name('products.reject');

    // User management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Merchants management
    Route::get('/merchants', [UserController::class, 'merchants'])->name('merchants.index');
});
