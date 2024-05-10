<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

//Route productos
Route::post('register', [AuthController::class, 'register'])
    ->name('register');

Route::post('me', [AuthController::class, 'me'])
    ->name('me');

Route::post('login', [AuthController::class, 'login'])
    ->name('login');

Route::get('user', [AuthController::class, 'me'])
    ->name('user');

Route::post('refresh', [AuthController::class, 'refresh'])
    ->name('refresh');

Route::post('logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::post('change-password', [AuthController::class, 'changePassword']);
