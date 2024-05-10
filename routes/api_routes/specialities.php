<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Doctor\SpecialityController;

Route::resource('specialities', SpecialityController::class);
Route::post('specialities/store', [SpecialityController::class, 'store'])->name('store');
Route::get('specialities/show/{role}', [SpecialityController::class, 'show'])->name('show');
Route::put('specialities/update/{role}', [SpecialityController::class, 'update'])->name('update');
Route::delete('specialities/destroy/{role}', [SpecialityController::class, 'destroy'])->name('destroy');

