<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Appointment\AppointmentAttentionController;

Route::post('appointment-atention/store', [AppointmentAttentionController::class, 'store'])->name('store');
Route::post('appointment-atention/store-local', [AppointmentAttentionController::class, 'storeLocal'])->name('storeLocal');
Route::get('appointment-atention/show/{id}', [AppointmentAttentionController::class, 'show'])->name('show');

