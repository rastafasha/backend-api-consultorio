<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Appointment\AppointmentController;

Route::post('appointments/filter', [AppointmentController::class, 'filter'])->name('appointment.filter');
Route::post('appointments/filterbydoctor/{doctor_id}/', [AppointmentController::class, 'filterByDoctor'])->name('appointment.filterByDoctor');
Route::get('appointments/config', [AppointmentController::class, 'config'])->name('appointment.config');
Route::get('appointments/patient', [AppointmentController::class, 'query_patient'])->name('appointment.query_patient');

Route::get('appointment', [AppointmentController::class, 'index'])->name('index');
Route::get('appointments/byDoctor/{doctor_id}/', [AppointmentController::class, 'appointmentByDoctor'])->name('appointment.appointmentByDoctor');

Route::get('appointments/atendidas/', [AppointmentController::class, 'atendidas'])->name('appointment.atendidas');
Route::get('appointments/pendientes', [AppointmentController::class, 'pendientes'])
->name('appointment.pendientes');

Route::get('appointments/pendientesbydoctor/{doctor_id}', [AppointmentController::class, 'pagosPendientesShowId'])
    ->name('appointment.pagosPendientesShowId');

Route::post('appointments/store', [AppointmentController::class, 'store'])->name('appointment.store');
Route::get('appointments/show/{id}', [AppointmentController::class, 'show'])->name('appointment.show');
Route::put('appointments/update/{appointment}', [AppointmentController::class, 'update'])->name('appointment.update');
Route::delete('appointments/destroy/{id}', [AppointmentController::class, 'destroy'])->name('appointment.destroy');

Route::get('appointments/atendidas/{id}', [AppointmentController::class, 'atendidas'])->name('appointment.atendidas');
// Route::get('appointments/byDoctor/{doctor_id}', [AppointmentController::class, 'appointmensByDoctor'])->name('appointmensByDoctor');

Route::post('appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointment.calendar');

Route::put('/appointments/update/cofirmation/{appointment:id}', [AppointmentController::class, 'updateConfirmation'])
    ->name('appointment.updateConfirmation');

   

Route::post('/appointment/{id}/cancel', [AppointmentController::class, 'cancelarCita'])
    ->name('appointment.cancelarCita');