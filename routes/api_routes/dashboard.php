<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardkpiController;

Route::get('dashboard/config', [DashboardkpiController::class, 'config'])->name('dashboard.config');
Route::post('dashboard/admin', [DashboardkpiController::class, 'dashboardAdmin'])->name('dashboard.dashboardAdmin');
Route::post('dashboard/admin-year', [DashboardkpiController::class, 'dashboardAdminYear'])->name('dashboard.dashboardAdminYear');
Route::post('dashboard/doctor', [DashboardkpiController::class, 'dashboardDoctor'])->name('dashboard.dashboardDoctor');
Route::post('dashboard/doctor-year', [DashboardkpiController::class, 'dashboardDoctorYear'])->name('dashboard.dashboardDoctorYear');
Route::post('dashboard/patient', [DashboardkpiController::class, 'dashboardPatient'])->name('dashboard.dashboardPatient');
Route::post('dashboard/patient-year', [DashboardkpiController::class, 'dashboardPatientYear'])->name('dashboard.dashboardPatientYear');
