<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Appointment\AppointmentController;

Route::post('appointment/filter', [AppointmentController::class, 'filter'])->name('filter');
Route::post('appointment/filterbydoctor/{doctor_id}/', [AppointmentController::class, 'filterByDoctor'])->name('filterByDoctor');
Route::get('appointment/config', [AppointmentController::class, 'config'])->name('config');
Route::get('appointment/patient', [AppointmentController::class, 'query_patient'])->name('query_patient');

Route::get('appointment', [AppointmentController::class, 'index'])->name('index');
Route::get('appointment/byDoctor/{doctor_id}/', [AppointmentController::class, 'appointmentByDoctor'])->name('appointmentByDoctor');

Route::get('appointment/atendidas/', [AppointmentController::class, 'atendidas'])->name('atendidas');
Route::get('appointment/pendientes', [AppointmentController::class, 'pendientes'])
->name('appointment.pendientes');

Route::get('appointment/pendientesbydoctor/{doctor_id}', [AppointmentController::class, 'pagosPendientesShowId'])
    ->name('appointment.pagosPendientesShowId');

Route::post('appointment/store', [AppointmentController::class, 'store'])->name('appointment.store');
Route::get('appointment/show/{id}', [AppointmentController::class, 'show'])->name('show');
Route::put('appointment/update/{appointment}', [AppointmentController::class, 'update'])->name('update');
Route::delete('appointment/destroy/{id}', [AppointmentController::class, 'destroy'])->name('destroy');

Route::get('appointment/atendidas/{id}', [AppointmentController::class, 'atendidas'])->name('atendidas');
// Route::get('appointment/byDoctor/{doctor_id}', [AppointmentController::class, 'appointmensByDoctor'])->name('appointmensByDoctor');

Route::post('appointment/calendar', [AppointmentController::class, 'calendar'])->name('calendar');

Route::put('/appointment/update/cofirmation/{appointment:id}', [AppointmentController::class, 'updateConfirmation'])
    ->name('appointment.updateConfirmation');

   

Route::post('/appointment/{id}/cancel', [AppointmentController::class, 'cancelarCita'])
    ->name('appointment.cancelarCita');