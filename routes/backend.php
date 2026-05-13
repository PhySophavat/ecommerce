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
    Route::get('/dashboard', [ProductController::class, 'dashboard'])->middleware('permission:dashboard.view')->name('dashboard');
    // Sliders
    Route::get('/sliders', [SlideController::class, 'index'])->middleware('permission:content.manage')->name('sliders.index');

    // Products management (all products)
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view')->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('permission:products.manage')->name('products.create');
    Route::get('/products/featured', [ProductController::class, 'featured'])->middleware('permission:content.manage')->name('products.featured');

    // Product approval/rejection
    Route::get('/products/pending', [ProductApprovalController::class, 'pending'])->middleware('permission:products.approve')->name('products.pending');
    Route::post('/products/{product}/approve', [ProductApprovalController::class, 'approve'])->middleware('permission:products.approve')->name('products.approve');
    Route::post('/products/{product}/reject', [ProductApprovalController::class, 'reject'])->middleware('permission:products.approve')->name('products.reject');

    // User management
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.manage')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.manage')->name('users.create');
    Route::get('/customers', [UserController::class, 'customers'])->middleware('permission:customers.manage')->name('customers.index');
    Route::get('/customers/details', [UserController::class, 'customerDetails'])->middleware('permission:customers.manage')->name('customers.details');
    Route::get('/customers/purchase-history', [UserController::class, 'purchaseHistory'])->middleware('permission:customers.manage')->name('customers.purchase-history');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.manage')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.manage')->name('users.destroy');

    // Merchants management
    Route::get('/merchants', [MerchantController::class, 'index'])->middleware('permission:merchants.manage')->name('merchants.index');
    Route::get('/merchants/pending', [MerchantController::class, 'pending'])->middleware('permission:merchants.manage')->name('merchants.pending');
    Route::get('/merchants/{merchant}', [MerchantController::class, 'show'])->middleware('permission:merchants.manage')->name('merchants.show');
    Route::post('/merchants/{merchant}/approve', [MerchantController::class, 'approve'])->middleware('permission:merchants.manage')->name('merchants.approve');
    Route::post('/merchants/{merchant}/reject', [MerchantController::class, 'reject'])->middleware('permission:merchants.manage')->name('merchants.reject');
    Route::post('/merchants/{merchant}/suspend', [MerchantController::class, 'suspend'])->middleware('permission:merchants.manage')->name('merchants.suspend');
    Route::post('/merchants/{merchant}/reactivate', [MerchantController::class, 'reactivate'])->middleware('permission:merchants.manage')->name('merchants.reactivate');

    Route::get('/merchant-balance', [WithdrawalPageController::class, 'merchantBalance'])->middleware('permission:wallet.view')->name('merchant-balance.index');
    Route::get('/settings/platform-fee', PlatformFeeSettingsPageController::class)->middleware('permission:settings.manage')->name('settings.platform-fee');

    Route::get('/qr-codes', [WithdrawalPageController::class, 'qrCodes'])->middleware('permission:wallet.view')->name('qr-codes.index');
    Route::get('/wallet', [WithdrawalPageController::class, 'wallet'])->middleware('permission:wallet.view')->name('wallet.index');
    Route::get('/finance-overview', [WithdrawalPageController::class, 'financeOverview'])->middleware('permission:reports.view')->name('finance-overview.index');
    Route::get('/bank-accounts', [WithdrawalPageController::class, 'bankAccounts'])->middleware('permission:wallet.manage')->name('bank-accounts.index');
    Route::get('/withdrawals', [WithdrawalPageController::class, 'withdrawals'])->middleware('permission:withdrawals.review')->name('withdrawals.index');
    Route::get('/deposits', [WithdrawalPageController::class, 'deposits'])->middleware('permission:payments.view')->name('deposits.index');
    Route::get('/payment-records', [WithdrawalPageController::class, 'paymentRecords'])->middleware('permission:payments.view')->name('payment-records.index');
    Route::get('/payment-methods', [WithdrawalPageController::class, 'paymentMethods'])->middleware('permission:payments.view')->name('payment-methods.index');
    Route::get('/payment-fees', [WithdrawalPageController::class, 'paymentFees'])->middleware('permission:payments.view')->name('payment-fees.index');
    Route::get('/orders', [OrderPageController::class, 'index'])->middleware('permission:orders.view')->name('orders.index');
    Route::get('/orders/pending', [OrderPageController::class, 'pending'])->middleware('permission:orders.view')->name('orders.pending');
    Route::get('/orders/processing', [OrderPageController::class, 'processing'])->middleware('permission:orders.view')->name('orders.processing');
    Route::get('/orders/shipped', [OrderPageController::class, 'shipped'])->middleware('permission:orders.view')->name('orders.shipped');
    Route::get('/orders/delivered', [OrderPageController::class, 'delivered'])->middleware('permission:orders.view')->name('orders.delivered');
    Route::get('/orders/cancelled', [OrderPageController::class, 'cancelled'])->middleware('permission:orders.view')->name('orders.cancelled');
    Route::get('/orders/refunded', [OrderPageController::class, 'refunded'])->middleware('permission:orders.view')->name('orders.refunded');
});
