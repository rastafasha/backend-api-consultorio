<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\LocationController;

Route::get('location', [LocationController::class, 'index'])->name('location.index');
Route::get('location/config', [LocationController::class, 'config'])->name('location.config');
Route::post('location/store', [LocationController::class, 'store'])->name('location.store');
Route::get('location/show/{id}', [LocationController::class, 'show'])->name('location.show');
Route::post('location/update/{id}', [LocationController::class, 'update'])->name('location.update');
Route::delete('location/destroy/{id}', [LocationController::class, 'destroy'])->name('location.destroy');
