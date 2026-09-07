<?php

use App\Http\Controllers\OdontogramaController;
use Illuminate\Support\Facades\Route;

Route::post('odontograma/store', [OdontogramaController::class, 'guardarHallazgo'])->name('odontograma.guardarHallazgo');
Route::get('odontograma/paciente/{patient_id}', [OdontogramaController::class, 'obtenerPorPaciente'])->name('odontograma.obtenerPorPaciente');

