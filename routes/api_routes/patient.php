<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patient\PatientController;

Route::get('patients', [PatientController::class, 'index'])->name('index');
Route::get('patients/byDoctor/{doctor_id}/', [PatientController::class, 'patientsByDoctor'])->name('patientsByDoctor');
Route::post('patients/store', [PatientController::class, 'store'])->name('store');
Route::get('patients/show/{id}', [PatientController::class, 'show'])->name('show');
Route::post('patients/update/{patient}', [PatientController::class, 'update'])->name('update');
Route::delete('patients/destroy/{id}', [PatientController::class, 'destroy'])->name('destroy');

Route::get('patients/profile/{id}', [PatientController::class, 'profile'])->name('profile');
Route::get('patients/shobypatienLocation/{location_id}', [PatientController::class, 'showPatientbyLocation'])->name('showPatientbyLocation');
