<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardkpiController;

Route::get('dashboard/config', [DashboardkpiController::class, 'config'])->name('dashboard.config');
Route::post('dashboard/admin', [DashboardkpiController::class, 'dashboard_admin'])->name('dashboard.dashboardAdmin');
Route::post('dashboard/admin-year', [DashboardkpiController::class, 'dashboard_admin_year'])->name('dashboard.dashboardAdminYear');
Route::post('dashboard/doctor', [DashboardkpiController::class, 'dashboard_doctor'])->name('dashboard.dashboardDoctor');
Route::post('dashboard/doctor-year', [DashboardkpiController::class, 'dashboard_doctor_year'])->name('dashboard.dashboardDoctorYear');
Route::post('dashboard/patient', [DashboardkpiController::class, 'dashboard_patient'])->name('dashboard.dashboardPatient');
Route::post('dashboard/patient-year', [DashboardkpiController::class, 'dashboard_patient_year'])->name('dashboard.dashboardPatientYear');
