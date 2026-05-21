<?php

use App\Http\Controllers\Api\Frontend\AuthController;
use App\Http\Controllers\Api\Frontend\CheckoutController;
use App\Http\Controllers\Api\Frontend\Home\HomeController;
use App\Http\Controllers\Api\Frontend\OrderController;
use App\Http\Controllers\Api\Frontend\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/home', HomeController::class)->name('home');
Route::get('/csrf-token', [AuthController::class, 'csrfToken'])->name('csrf-token');
Route::get('/session', [AuthController::class, 'session'])->name('session');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');

Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/payment-status', [PaymentController::class, 'status'])->name('orders.payment-status');
    Route::post('/orders/{order}/payment-proof', [PaymentController::class, 'submitProof'])->name('orders.payment-proof');
    Route::post('/checkout', CheckoutController::class)->name('checkout');
    Route::post('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
});
