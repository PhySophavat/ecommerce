<?php

use App\Http\Controllers\Api\Frontend\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/home', HomeController::class)->name('home');
