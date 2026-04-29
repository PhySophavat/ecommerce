<?php

use App\Http\Controllers\Merchant\Product\ProductController as MerchantProductController;
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
    Route::get('/dashboard', [MerchantProductController::class, 'dashboard'])->name('dashboard');

    // My Products
    Route::get('/products', [MerchantProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [MerchantProductController::class, 'create'])->name('products.create');
    Route::post('/products', [MerchantProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [MerchantProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [MerchantProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [MerchantProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [MerchantProductController::class, 'destroy'])->name('products.destroy');

    // Product status
    Route::get('/products/pending', [MerchantProductController::class, 'pending'])->name('products.pending');
    Route::get('/products/rejected', [MerchantProductController::class, 'rejected'])->name('products.rejected');
    Route::get('/products/approved', [MerchantProductController::class, 'approved'])->name('products.approved');

    Route::get('/wallet', [WithdrawalPageController::class, 'wallet'])->name('wallet');
    Route::get('/deposits', [WithdrawalPageController::class, 'deposit'])->name('deposits');
    Route::get('/bank-accounts', [WithdrawalPageController::class, 'bankAccounts'])->name('bank-accounts');
    Route::get('/withdrawals', [WithdrawalPageController::class, 'withdraw'])->name('withdrawals');
    Route::get('/withdrawals/history', [WithdrawalPageController::class, 'history'])->name('withdrawals.history');
    Route::get('/wallet/transactions', [WithdrawalPageController::class, 'history'])->name('wallet.transactions');
});
