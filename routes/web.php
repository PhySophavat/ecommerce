<?php

use App\Http\Controllers\Api\Frontend\Home\HomeController as FrontendApiHomeController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/backend', '/admin/products');
Route::redirect('/backend/products', '/admin/products');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');

Route::name('frontend.')->group(base_path('routes/frontend.php'));
Route::prefix('admin')->name('admin.')->group(base_path('routes/backend.php'));
Route::prefix('api/frontend')->name('api.frontend.')->group(base_path('routes/api_frontend.php'));
Route::prefix('api/admin')->name('api.admin.')->group(base_path('routes/api_backend.php'));

// Authentication routes
Route::prefix('auth')->name('auth.')->group(base_path('routes/auth.php'));

// Merchant routes
Route::prefix('merchant')->name('merchant.')->group(base_path('routes/merchant.php'));

Route::prefix('api')->name('api.legacy.')->group(function (): void {
    Route::get('/storefront', FrontendApiHomeController::class)->name('storefront');
});
