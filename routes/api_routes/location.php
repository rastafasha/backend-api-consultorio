<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LocationController;

Route::get('location', [LocationController::class, 'index'])->name('index');
Route::get('location/config', [LocationController::class, 'config'])->name('config');
Route::post('location/store', [LocationController::class, 'store'])->name('store');
Route::get('location/show/{id}', [LocationController::class, 'show'])->name('show');
Route::post('location/update/{id}', [LocationController::class, 'update'])->name('update');
Route::delete('location/destroy/{id}', [LocationController::class, 'destroy'])->name('destroy');
