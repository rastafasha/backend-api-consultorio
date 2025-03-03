<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Doctor\SpecialityController;

Route::resource('specialities', SpecialityController::class);
Route::post('specialities/store', [SpecialityController::class, 'store'])->name('specialitie.store');
Route::get('specialities/show/{id}', [SpecialityController::class, 'showId'])->name('specialitie.showId');
Route::get('specialities/show/{role}', [SpecialityController::class, 'show'])->name('specialitie.show');
Route::put('specialities/update/{role}', [SpecialityController::class, 'update'])->name('specialitie.update');
Route::delete('specialities/destroy/{role}', [SpecialityController::class, 'destroy'])->name('specialitie.destroy');
