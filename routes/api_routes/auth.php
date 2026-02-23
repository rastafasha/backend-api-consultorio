<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ChangeForgotPasswordControllerController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

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

Route::post('change-forgot-password', [ChangeForgotPasswordControllerController::class, 'changeForgotPassword']);

Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPassword']);

Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
