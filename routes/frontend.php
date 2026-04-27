<?php

use App\Http\Controllers\Frontend\Home\HomeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/frontend');
Route::get('/frontend', HomeController::class)->name('home');
