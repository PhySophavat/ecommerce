<?php

use App\Http\Controllers\Backend\SlideManagementController;
use App\Http\Controllers\Backend\ProductManagementController;
use App\Http\Controllers\Backend\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [ProductManagementController::class, 'dashboard'])->name('dashboard');
Route::get('/sliders', [SlideManagementController::class, 'index'])->name('sliders.index');
Route::get('/products', [ProductManagementController::class, 'index'])->name('products.index');
Route::get('/products/create', [ProductManagementController::class, 'create'])->name('products.create');
Route::get('/products/featured', [ProductManagementController::class, 'featured'])->name('products.featured');
Route::redirect('/users/create', '/admin/products')->name('users.create');
Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
