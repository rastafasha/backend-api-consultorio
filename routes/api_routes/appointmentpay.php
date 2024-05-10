<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Appointment\AppointmentPayController;

Route::get('appointmentpay', [AppointmentPayController::class, 'index'])->name('index');
Route::post('appointmentpay/store', [AppointmentPayController::class, 'store'])->name('store');
Route::get('appointmentpay/show/{id}', [AppointmentPayController::class, 'show'])->name('show');
Route::put('appointmentpay/update/{appointmentpay}', [AppointmentPayController::class, 'update'])->name('update');
Route::delete('appointmentpay/destroy/{id}', [AppointmentPayController::class, 'destroy'])->name('destroy');
Route::get('appointmentpay/byDoctor/{doctor_id}', [AppointmentPayController::class, 'paymentsByDoctor'])->name('paymentsByDoctor');

