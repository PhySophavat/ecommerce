<?php

use App\Http\Controllers\Api\Frontend\Home\HomeController as FrontendApiHomeController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::redirect('/backend', '/admin/products');
Route::redirect('/backend/products', '/admin/products');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::get('/khqr-preview', function (Request $request) {
    return view('khqr-preview', [
        'title' => 'KHQR Preview',
        'mountVueApp' => false,
        'context' => ['app' => 'backend-preview'],
        'bankName' => (string) $request->query('bank', 'ABA'),
        'amount' => number_format(max((float) $request->query('amount', 0), 0), 2, '.', ''),
        'merchantName' => (string) $request->query('merchant', 'Merchant Shop'),
        'receiverName' => (string) $request->query('receiver', 'E-commerce KHQR Collection'),
        'khqrCode' => (string) $request->query('code', ''),
        'imageToken' => (string) $request->query('image_token', ''),
    ]);
})->name('khqr.preview');

Route::name('frontend.')->group(base_path('routes/frontend.php'));
Route::prefix('admin')->name('admin.')->group(base_path('routes/backend.php'));
Route::prefix('api/frontend')->name('api.frontend.')->group(base_path('routes/api_frontend.php'));
Route::prefix('api/admin')->name('api.admin.')->group(base_path('routes/api_backend.php'));
Route::prefix('api/merchant')->name('api.merchant.')->group(base_path('routes/api_merchant.php'));

// Authentication routes
Route::prefix('auth')->name('auth.')->group(base_path('routes/auth.php'));

// Merchant routes
Route::prefix('merchant')->name('merchant.')->group(base_path('routes/merchant.php'));

Route::prefix('api')->name('api.legacy.')->group(function (): void {
    Route::get('/storefront', FrontendApiHomeController::class)->name('storefront');
});
