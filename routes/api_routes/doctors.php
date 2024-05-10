<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Doctor\DoctorController;


Route::get('doctors', [DoctorController::class, 'index'])->name('index');
Route::get('doctors/config', [DoctorController::class, 'config'])->name('config');
Route::post('doctors/store', [DoctorController::class, 'store'])->name('store');
Route::get('doctors/show/{id}', [DoctorController::class, 'show'])->name('show');
Route::post('doctors/update/{id}', [DoctorController::class, 'update'])->name('update');
Route::delete('doctors/destroy/{id}', [DoctorController::class, 'destroy'])->name('destroy');

Route::get('doctors/profile/{id}', [DoctorController::class, 'profile'])->name('profile');

