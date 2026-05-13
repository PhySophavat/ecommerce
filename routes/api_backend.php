<?php

use App\Http\Controllers\Api\Backend\Product\ProductController;
use App\Http\Controllers\Api\Backend\OrderController;
use App\Http\Controllers\Api\Backend\BankAccountController;
use App\Http\Controllers\Api\Backend\DepositController;
use App\Http\Controllers\Api\Backend\FinanceOverviewController;
use App\Http\Controllers\Api\Backend\MerchantBalanceController;
use App\Http\Controllers\Api\Backend\PaymentFeeController;
use App\Http\Controllers\Api\Backend\PaymentMethodController;
use App\Http\Controllers\Api\Backend\QrCodeController;
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
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.manage')->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view')->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.manage')->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.manage')->name('products.destroy');
    Route::get('/slides/dashboard', [SlideDashboardController::class, 'index'])->middleware('permission:content.manage')->name('slides.dashboard');
    Route::post('/slides', [SlideController::class, 'store'])->middleware('permission:content.manage')->name('slides.store');
    Route::put('/slides/{slide}', [SlideController::class, 'update'])->middleware('permission:content.manage')->name('slides.update');
    Route::delete('/slides/{slide}', [SlideController::class, 'destroy'])->middleware('permission:content.manage')->name('slides.destroy');
    Route::get('/platform-fee-settings', [PlatformFeeSettingsController::class, 'show'])->middleware('permission:settings.manage')->name('platform-fee-settings.show');
    Route::put('/platform-fee-settings', [PlatformFeeSettingsController::class, 'update'])->middleware('permission:settings.manage')->name('platform-fee-settings.update');

    Route::get('/wallet', WalletController::class)->middleware('permission:wallet.view')->name('wallet.show');
    Route::get('/qr-codes', QrCodeController::class)->middleware('permission:wallet.view')->name('qr-codes.index');
    Route::get('/finance-overview', FinanceOverviewController::class)->middleware('permission:reports.view')->name('finance-overview');
    Route::get('/merchant-balance', MerchantBalanceController::class)->middleware('permission:wallet.view')->name('merchant-balance.index');
    Route::get('/deposits', [DepositController::class, 'index'])->middleware('permission:payments.view')->name('deposits.index');
    Route::put('/deposits/{deposit}/approve', [DepositController::class, 'approve'])->middleware('permission:payments.view')->name('deposits.approve');
    Route::put('/deposits/{deposit}/reject', [DepositController::class, 'reject'])->middleware('permission:payments.view')->name('deposits.reject');
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->middleware('permission:wallet.manage')->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->middleware('permission:wallet.manage')->name('bank-accounts.store');
    Route::put('/bank-accounts/{bankAccount}/approve', [BankAccountController::class, 'approve'])->middleware('permission:wallet.manage')->name('bank-accounts.approve');
    Route::put('/bank-accounts/{bankAccount}/reject', [BankAccountController::class, 'reject'])->middleware('permission:wallet.manage')->name('bank-accounts.reject');
    Route::put('/bank-accounts/{bankAccount}/disable', [BankAccountController::class, 'disable'])->middleware('permission:wallet.manage')->name('bank-accounts.disable');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->middleware('permission:wallet.manage')->name('bank-accounts.destroy');
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->middleware('permission:withdrawals.review')->name('withdrawals.index');
    Route::put('/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->middleware('permission:withdrawals.review')->name('withdrawals.approve');
    Route::put('/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->middleware('permission:withdrawals.review')->name('withdrawals.reject');
    Route::put('/withdrawals/{withdrawal}/mark-paid', [WithdrawalController::class, 'markPaid'])->middleware('permission:withdrawals.review')->name('withdrawals.mark-paid');
    Route::get('/payment-methods', PaymentMethodController::class)->middleware('permission:payments.view')->name('payment-methods.index');
    Route::get('/payment-fees', PaymentFeeController::class)->middleware('permission:payments.view')->name('payment-fees.index');
    Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.manage')->name('orders.update-status');
    Route::put('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->middleware('permission:orders.manage')->name('orders.update-payment-status');
});
