<?php

use App\Http\Controllers\Api\Backend\Product\ProductController;
use App\Http\Controllers\Api\Backend\OrderController;
use App\Http\Controllers\Api\Backend\BankAccountController;
use App\Http\Controllers\Api\Backend\DepositController;
use App\Http\Controllers\Api\Backend\MerchantBalanceController;
use App\Http\Controllers\Api\Backend\PaymentMethodController;
use App\Http\Controllers\Api\Backend\WalletController;
use App\Http\Controllers\Api\Backend\WithdrawalController;
use App\Http\Controllers\Api\Backend\Settings\PlatformFeeSettingsController;
use App\Http\Controllers\Api\Backend\Slide\SlideController;
use App\Http\Controllers\Api\Backend\Slide\SlideDashboardController;
use App\Http\Controllers\Api\Backend\AuthController;
use Illuminate\Support\Facades\Route;

// Public API routes for authentication
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected backend API routes - admin only
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
    Route::get('/platform-fee-settings', [PlatformFeeSettingsController::class, 'show'])->name('platform-fee-settings.show');
    Route::put('/platform-fee-settings', [PlatformFeeSettingsController::class, 'update'])->name('platform-fee-settings.update');

    Route::get('/wallet', WalletController::class)->name('wallet.show');
    Route::get('/merchant-balance', MerchantBalanceController::class)->name('merchant-balance.index');
    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::put('/deposits/{deposit}/approve', [DepositController::class, 'approve'])->name('deposits.approve');
    Route::put('/deposits/{deposit}/reject', [DepositController::class, 'reject'])->name('deposits.reject');
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('/bank-accounts/{bankAccount}/approve', [BankAccountController::class, 'approve'])->name('bank-accounts.approve');
    Route::put('/bank-accounts/{bankAccount}/reject', [BankAccountController::class, 'reject'])->name('bank-accounts.reject');
    Route::put('/bank-accounts/{bankAccount}/disable', [BankAccountController::class, 'disable'])->name('bank-accounts.disable');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::put('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::put('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::put('/withdrawals/{withdrawal}/mark-paid', [WithdrawalController::class, 'markPaid'])->name('withdrawals.mark-paid');
    Route::get('/payment-methods', PaymentMethodController::class)->name('payment-methods.index');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::put('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.update-payment-status');
});
