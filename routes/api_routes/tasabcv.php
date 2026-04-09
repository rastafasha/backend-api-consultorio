<?php

use Illuminate\Http\Request;
use App\Http\Controllers\TasabcbController;
use Illuminate\Support\Facades\Route;

Route::get('tasabcv/ultimatasa', [TasabcbController::class, 'ultimatasa'])->name('tasas.ultimatasa');
Route::resource('tasabcv', TasabcbController::class);
Route::get('tasabcv/show/{tasa}', [TasabcbController::class, 'show'])->name('tasas.show');
Route::post('tasabcv/store', [TasabcbController::class, 'store'])->name('tasas.store');
Route::put('tasabcv/update/{tasa}', [TasabcbController::class, 'update'])->name('tasas.update');
Route::delete('tasabcv/destroy/{tasa}', [TasabcbController::class, 'destroy'])->name('tasas.destroy');

