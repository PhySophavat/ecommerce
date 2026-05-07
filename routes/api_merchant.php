<?php

use App\Http\Controllers\Api\Merchant\BankAccountController;
use App\Http\Controllers\Api\Merchant\DepositController;
use App\Http\Controllers\Api\Merchant\FinanceOverviewController;
use App\Http\Controllers\Api\Merchant\OrderController;
use App\Http\Controllers\Api\Merchant\ProductController;
use App\Http\Controllers\Api\Merchant\WalletController;
use App\Http\Controllers\Api\Merchant\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:merchant', 'merchant.approved'])->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.manage')->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view')->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.manage')->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.manage')->name('products.destroy');

    Route::get('/wallet', [WalletController::class, 'show'])->middleware('permission:wallet.view')->name('wallet.show');
    Route::get('/finance-overview', FinanceOverviewController::class)->middleware('permission:reports.view')->name('finance-overview');
    Route::get('/wallet/transactions', [WalletController::class, 'transactions'])->middleware('permission:wallet.view')->name('wallet.transactions');
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->middleware('permission:wallet.manage')->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->middleware('permission:wallet.manage')->name('bank-accounts.store');
    Route::put('/bank-accounts/{id}', [BankAccountController::class, 'update'])->middleware('permission:wallet.manage')->name('bank-accounts.update');
    Route::delete('/bank-accounts/{id}', [BankAccountController::class, 'destroy'])->middleware('permission:wallet.manage')->name('bank-accounts.destroy');

    Route::get('/deposits', [DepositController::class, 'index'])->middleware('permission:wallet.manage')->name('deposits.index');
    Route::post('/deposits', [DepositController::class, 'store'])->middleware('permission:wallet.manage')->name('deposits.store');
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->middleware('permission:withdrawals.request')->name('withdrawals.index');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->middleware('permission:withdrawals.request')->name('withdrawals.store');
    Route::get('/orders', [OrderController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('permission:orders.view')->name('orders.show');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:orders.manage')->name('orders.update-status');
});
