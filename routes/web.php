<?php

use App\Http\Controllers\Api\Frontend\HomeController as FrontendApiHomeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/backend', '/admin/users/create');
Route::redirect('/backend/products', '/admin/users/create');

Route::name('frontend.')->group(base_path('routes/frontend.php'));
Route::prefix('admin')->name('admin.')->group(base_path('routes/backend.php'));
Route::prefix('api/frontend')->name('api.frontend.')->group(base_path('routes/api_frontend.php'));

Route::prefix('api')->name('api.legacy.')->group(function (): void {
    Route::get('/storefront', FrontendApiHomeController::class)->name('storefront');
});
