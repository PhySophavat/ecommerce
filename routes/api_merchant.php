<?php

use App\Http\Controllers\Api\Merchant\BankAccountController;
use App\Http\Controllers\Api\Merchant\DepositController;
use App\Http\Controllers\Api\Merchant\WalletController;
use App\Http\Controllers\Api\Merchant\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:merchant', 'merchant.approved'])->group(function () {
    Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::get('/wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('/bank-accounts/{id}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{id}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    Route::get('/deposits', [DepositController::class, 'index'])->name('deposits.index');
    Route::post('/deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
});
