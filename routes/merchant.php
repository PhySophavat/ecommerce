<?php

use App\Http\Controllers\Merchant\Product\ProductController as MerchantProductController;
use App\Http\Controllers\Merchant\Order\OrderController as MerchantOrderController;
use App\Http\Controllers\Merchant\RegistrationController;
use App\Http\Controllers\Merchant\Finance\WithdrawalPageController;
use Illuminate\Support\Facades\Route;

// Public merchant registration routes (before login)
Route::get('/register', [RegistrationController::class, 'step1'])->name('register');
Route::get('/register/step1', [RegistrationController::class, 'step1'])->name('register.step1');
Route::post('/register/step1', [RegistrationController::class, 'storeStep1'])->name('register.step1.store');
Route::get('/register/step2', [RegistrationController::class, 'step2'])->name('register.step2');
Route::post('/register/step2', [RegistrationController::class, 'storeStep2'])->name('register.step2.store');
Route::get('/register/step3', [RegistrationController::class, 'step3'])->name('register.step3');
Route::post('/register/step3', [RegistrationController::class, 'storeStep3'])->name('register.step3.store');

Route::middleware(['auth', 'role:merchant'])->group(function () {
    Route::get('/status', [RegistrationController::class, 'status'])->name('status');
});

// Merchant routes - approved merchants only
Route::middleware(['auth', 'role:merchant', 'merchant.approved'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [MerchantProductController::class, 'dashboard'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/orders', [MerchantOrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('/orders/pending', [MerchantOrderController::class, 'pending'])->middleware('permission:orders.view')->name('orders.pending');
    Route::get('/orders/processing', [MerchantOrderController::class, 'processing'])->middleware('permission:orders.view')->name('orders.processing');
    Route::get('/orders/shipped', [MerchantOrderController::class, 'shipped'])->middleware('permission:orders.view')->name('orders.shipped');
    Route::get('/orders/delivered', [MerchantOrderController::class, 'delivered'])->middleware('permission:orders.view')->name('orders.delivered');
    Route::get('/orders/cancelled', [MerchantOrderController::class, 'cancelled'])->middleware('permission:orders.view')->name('orders.cancelled');
    Route::get('/orders/refunded', [MerchantOrderController::class, 'refunded'])->middleware('permission:orders.view')->name('orders.refunded');

    // My Products
    Route::get('/products', [MerchantProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
    Route::get('/products/create', [MerchantProductController::class, 'create'])->middleware('permission:products.manage')->name('products.create');
    Route::post('/products', [MerchantProductController::class, 'store'])->middleware('permission:products.manage')->name('products.store');
    Route::get('/products/{product}', [MerchantProductController::class, 'show'])->middleware('permission:products.view')->name('products.show');
    Route::get('/products/{product}/edit', [MerchantProductController::class, 'edit'])->middleware('permission:products.manage')->name('products.edit');
    Route::put('/products/{product}', [MerchantProductController::class, 'update'])->middleware('permission:products.manage')->name('products.update');
    Route::delete('/products/{product}', [MerchantProductController::class, 'destroy'])->middleware('permission:products.manage')->name('products.destroy');

    // Product status
    Route::get('/products/pending', [MerchantProductController::class, 'pending'])->middleware('permission:products.view')->name('products.pending');
    Route::get('/products/rejected', [MerchantProductController::class, 'rejected'])->middleware('permission:products.view')->name('products.rejected');
    Route::get('/products/approved', [MerchantProductController::class, 'approved'])->middleware('permission:products.view')->name('products.approved');

    Route::get('/wallet', [WithdrawalPageController::class, 'wallet'])->middleware('permission:wallet.view')->name('wallet');
    Route::get('/qr-codes', [WithdrawalPageController::class, 'wallet'])->middleware('permission:wallet.view')->name('qr-codes');
    Route::get('/finance-overview', [WithdrawalPageController::class, 'overview'])->middleware('permission:reports.view')->name('finance-overview');
    Route::get('/deposits', [WithdrawalPageController::class, 'deposit'])->middleware('permission:wallet.manage')->name('deposits');
    Route::get('/bank-accounts', [WithdrawalPageController::class, 'bankAccounts'])->middleware('permission:wallet.manage')->name('bank-accounts');
    Route::get('/withdrawals', [WithdrawalPageController::class, 'withdraw'])->middleware('permission:withdrawals.request')->name('withdrawals');
    Route::get('/withdrawals/history', [WithdrawalPageController::class, 'history'])->middleware('permission:withdrawals.request')->name('withdrawals.history');
    Route::get('/wallet/transactions', [WithdrawalPageController::class, 'history'])->middleware('permission:wallet.view')->name('wallet.transactions');
});
