<?php

use App\Http\Controllers\Backend\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::redirect('/products', '/admin/users/create');
Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
