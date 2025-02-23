<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Patient\PatientController;

Route::get('patients', [PatientController::class, 'index'])->name('patient.index');
Route::get('patients/byDoctor/{doctor_id}/', [PatientController::class, 'patientsByDoctor'])->name('patient.patientsByDoctor');
Route::post('patients/store', [PatientController::class, 'store'])->name('patient.store');
Route::get('patients/show/{id}', [PatientController::class, 'show'])->name('patient.show');
Route::post('patients/update/{patient}', [PatientController::class, 'update'])->name('patient.update');
Route::delete('patients/destroy/{id}', [PatientController::class, 'destroy'])->name('patient.destroy');

Route::get('patients/profile/{id}', [PatientController::class, 'profile'])->name('patient.profile');
Route::get('patients/shobypatienLocation/{location_id}', [PatientController::class, 'showPatientbyLocation'])->name('patient.showPatientbyLocation');
