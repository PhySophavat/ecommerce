<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'storefront')->name('storefront');

Route::prefix('api')->group(function (): void {
    Route::get('/storefront', StorefrontController::class)->name('api.storefront');
    Route::post('/cart/items', [CartController::class, 'store'])->name('api.cart.store');
    Route::patch('/cart/items/{product}', [CartController::class, 'update'])
        ->whereNumber('product')
        ->name('api.cart.update');
    Route::delete('/cart/items/{product}', [CartController::class, 'destroy'])
        ->whereNumber('product')
        ->name('api.cart.destroy');
    Route::post('/checkout', CheckoutController::class)->name('api.checkout');
});
