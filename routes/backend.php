<?php

use App\Http\Controllers\Backend\Product\ProductController;
use App\Http\Controllers\Backend\Slide\SlideController;
use App\Http\Controllers\Backend\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [ProductController::class, 'dashboard'])->name('dashboard');
Route::get('/sliders', [SlideController::class, 'index'])->name('sliders.index');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
Route::get('/products/featured', [ProductController::class, 'featured'])->name('products.featured');
Route::redirect('/users/create', '/admin/products')->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
