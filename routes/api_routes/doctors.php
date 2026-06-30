<?php

use App\Http\Controllers\Admin\Doctor\DoctorAddressController;
use App\Http\Controllers\Admin\Doctor\DoctorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('doctors', [DoctorController::class, 'index'])->name('doctor.index');
Route::get('doctors/config', [DoctorController::class, 'config'])->name('doctor.config');
Route::post('doctors/store', [DoctorController::class, 'store'])->name('doctor.store');
Route::get('doctors/show/{id}', [DoctorController::class, 'show'])->name('doctor.show');
Route::post('doctors/update/{id}', [DoctorController::class, 'update'])->name('doctor.update');
Route::delete('doctors/destroy/{id}', [DoctorController::class, 'destroy'])->name('doctor.destroy');
Route::put('/doctors/update/status/{id}', [DoctorController::class, 'updateStatus'])
    ->name('doctor.updateStatus');
Route::get('doctors/profile/{id}', [DoctorController::class, 'profile'])->name('doctor.profile');

// 🏥 RUTAS DE CONTROL PARA CONSULTORIOS (VINCULADAS A TU DOCTORADDRESSCONTROLLER)
Route::get('doctor-addresses/doctor/{user_id}', [DoctorAddressController::class, 'getByDoctor'])->name('doctor.address.by_doctor');
Route::post('doctor-addresses/store', [DoctorAddressController::class, 'store'])->name('doctor.address.store');
Route::post('doctor-addresses/update/{id}', [DoctorAddressController::class, 'update'])->name('doctor.address.update'); // Nota: Usamos POST por compatibilidad de FormData si mandaras archivos en el futuro
Route::delete('doctor-addresses/destroy/{id}', [DoctorAddressController::class, 'destroy'])->name('doctor.address.destroy');
