
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PresupuestoController;

Route::post('presupuesto/filter', [PresupuestoController::class, 'filter'])->name('filter');
Route::get('presupuesto/config', [PresupuestoController::class, 'config'])->name('config');
Route::get('presupuesto/patient', [PresupuestoController::class, 'query_patient'])->name('query_patient');

Route::get('presupuesto', [PresupuestoController::class, 'index'])->name('index');
Route::post('presupuesto/store', [PresupuestoController::class, 'store'])->name('presupuesto.store');
Route::get('presupuesto/show/{id}', [PresupuestoController::class, 'show'])->name('show');
Route::put('presupuesto/update/{presupuesto}', [PresupuestoController::class, 'update'])->name('update');
Route::delete('presupuesto/destroy/{id}', [PresupuestoController::class, 'destroy'])->name('destroy');

Route::put('/presupuesto/update/cofirmation/{presupuesto:id}', [PresupuestoController::class, 'updateConfirmation'])
    ->name('presupuesto.updateConfirmation');