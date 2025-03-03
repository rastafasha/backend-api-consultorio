<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Appointment\AppointmentPayController;

Route::get('appointmentpay/', [AppointmentPayController::class, 'index'])->name('appointmentpay.index');
Route::get('appointmentpay/byDoctor/{doctor_id}/', [AppointmentPayController::class, 'paymentsByDoctor'])->name('appointmentpay.paymentsByDoctor');
Route::get('appointmentpay/show/{id}', [AppointmentPayController::class, 'show'])->name('appointmentpay.show');

Route::post('appointmentpay/store', [AppointmentPayController::class, 'store'])->name('appointmentpay.store');
Route::put('appointmentpay/update/{appointmentpay}', [AppointmentPayController::class, 'update'])->name('appointmentpay.update');
Route::delete('appointmentpay/destroy/{id}', [AppointmentPayController::class, 'destroy'])->name('appointmentpay.destroy');
