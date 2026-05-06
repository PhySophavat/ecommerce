<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Backend\Product\ProductController;
use App\Http\Controllers\Backend\Order\OrderController as OrderPageController;
use App\Http\Controllers\Backend\Product\ProductApprovalController;
use App\Http\Controllers\Backend\Settings\PlatformFeeSettingsPageController;
use App\Http\Controllers\Backend\Slide\SlideController;
use App\Http\Controllers\Backend\User\UserController;
use App\Http\Controllers\Backend\Merchant\MerchantController;
use App\Http\Controllers\Backend\WithdrawalPageController;
use Illuminate\Support\Facades\Route;

// Public admin login page (before authentication)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/', function () {
    return redirect()->route('admin.login');
})->name('home');

// Backend routes - admin only
Route::middleware(['auth', 'role:admin', 'admin.otp'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [ProductController::class, 'dashboard'])->name('dashboard');
    // Sliders
    Route::get('/sliders', [SlideController::class, 'index'])->name('sliders.index');

    // Products management (all products)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/featured', [ProductController::class, 'featured'])->name('products.featured');

    // Product approval/rejection
    Route::get('/products/pending', [ProductApprovalController::class, 'pending'])->name('products.pending');
    Route::post('/products/{product}/approve', [ProductApprovalController::class, 'approve'])->name('products.approve');
    Route::post('/products/{product}/reject', [ProductApprovalController::class, 'reject'])->name('products.reject');

    // User management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::get('/customers', [UserController::class, 'customers'])->name('customers.index');
    Route::get('/customers/details', [UserController::class, 'customerDetails'])->name('customers.details');
    Route::get('/customers/purchase-history', [UserController::class, 'purchaseHistory'])->name('customers.purchase-history');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Merchants management
    Route::get('/merchants', [MerchantController::class, 'index'])->name('merchants.index');
    Route::get('/merchants/pending', [MerchantController::class, 'pending'])->name('merchants.pending');
    Route::get('/merchants/{merchant}', [MerchantController::class, 'show'])->name('merchants.show');
    Route::post('/merchants/{merchant}/approve', [MerchantController::class, 'approve'])->name('merchants.approve');
    Route::post('/merchants/{merchant}/reject', [MerchantController::class, 'reject'])->name('merchants.reject');
    Route::post('/merchants/{merchant}/suspend', [MerchantController::class, 'suspend'])->name('merchants.suspend');
    Route::post('/merchants/{merchant}/reactivate', [MerchantController::class, 'reactivate'])->name('merchants.reactivate');

    Route::get('/merchant-balance', [WithdrawalPageController::class, 'merchantBalance'])->name('merchant-balance.index');
    Route::get('/settings/platform-fee', PlatformFeeSettingsPageController::class)->name('settings.platform-fee');

    Route::get('/wallet', [WithdrawalPageController::class, 'wallet'])->name('wallet.index');
    Route::get('/bank-accounts', [WithdrawalPageController::class, 'bankAccounts'])->name('bank-accounts.index');
    Route::get('/withdrawals', [WithdrawalPageController::class, 'withdrawals'])->name('withdrawals.index');
    Route::get('/deposits', [WithdrawalPageController::class, 'deposits'])->name('deposits.index');
    Route::get('/payment-records', [WithdrawalPageController::class, 'paymentRecords'])->name('payment-records.index');
    Route::get('/payment-methods', [WithdrawalPageController::class, 'paymentMethods'])->name('payment-methods.index');
    Route::get('/orders', [OrderPageController::class, 'index'])->name('orders.index');
    Route::get('/orders/pending', [OrderPageController::class, 'pending'])->name('orders.pending');
    Route::get('/orders/processing', [OrderPageController::class, 'processing'])->name('orders.processing');
    Route::get('/orders/shipped', [OrderPageController::class, 'shipped'])->name('orders.shipped');
    Route::get('/orders/delivered', [OrderPageController::class, 'delivered'])->name('orders.delivered');
    Route::get('/orders/cancelled', [OrderPageController::class, 'cancelled'])->name('orders.cancelled');
    Route::get('/orders/refunded', [OrderPageController::class, 'refunded'])->name('orders.refunded');
});
