<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Doctor\DoctorController;


Route::get('doctors', [DoctorController::class, 'index'])->name('doctor.index');
Route::get('doctors/config', [DoctorController::class, 'config'])->name('doctor.config');
Route::post('doctors/store', [DoctorController::class, 'store'])->name('doctor.store');
Route::get('doctors/show/{id}', [DoctorController::class, 'show'])->name('doctor.show');
Route::post('doctors/update/{id}', [DoctorController::class, 'update'])->name('doctor.update');
Route::delete('doctors/destroy/{id}', [DoctorController::class, 'destroy'])->name('doctor.destroy');
Route::put('/doctorupdate/status/{id}', [DoctorController::class, 'updateStatus'])
    ->name('doctor.updateStatus');
Route::get('doctors/profile/{id}', [DoctorController::class, 'profile'])->name('doctor.profile');

