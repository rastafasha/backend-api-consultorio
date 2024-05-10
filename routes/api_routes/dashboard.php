<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardkpiController;



Route::get('dashboard/config', [DashboardkpiController::class, 'config'])->name('config');
Route::post('dashboard/admin', [DashboardkpiController::class, 'dashboard_admin'])->name('dashboard_admin');
Route::post('dashboard/admin-year', [DashboardkpiController::class, 'dashboard_admin_year'])->name('dashboard_admin_year');
Route::post('dashboard/doctor', [DashboardkpiController::class, 'dashboard_doctor'])->name('dashboard_doctor');
Route::post('dashboard/doctor-year', [DashboardkpiController::class, 'dashboard_doctor_year'])->name('dashboard_doctor_year');
Route::post('dashboard/patient', [DashboardkpiController::class, 'dashboard_patient'])->name('dashboard_patient');
Route::post('dashboard/patient-year', [DashboardkpiController::class, 'dashboard_patient_year'])->name('dashboard_patient_year');