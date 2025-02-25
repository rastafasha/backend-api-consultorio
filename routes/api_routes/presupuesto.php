
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PresupuestoController;

Route::get('presupuesto/byDoctor/{doctor_id}/', [PresupuestoController::class, 'presupuestoByDoctor'])
->name('presupuesto.presupuestoByDoctor');
Route::post('presupuesto/filter', [PresupuestoController::class, 'filter'])->name('presupuesto.filter');
Route::get('presupuesto/config', [PresupuestoController::class, 'config'])->name('presupuesto.config');
Route::get('presupuesto/patient', [PresupuestoController::class, 'query_patient'])->name('presupuesto.query_patient');

Route::get('presupuesto', [PresupuestoController::class, 'index'])->name('presupuesto.index');
Route::post('presupuesto/store', [PresupuestoController::class, 'store'])->name('presupuesto.store');
Route::get('presupuesto/show/{id}', [PresupuestoController::class, 'show'])->name('presupuesto.show');
Route::put('presupuesto/update/{id}', [PresupuestoController::class, 'update'])->name('presupuesto.update');

Route::delete('presupuesto/destroy/{id}', [PresupuestoController::class, 'destroy'])->name('presupuesto.destroy');

Route::delete('presupuesto/destroy/item/{presupuesto_id}/{presupuestoitem_id}', [PresupuestoController::class, 'destroyitem'])
->name('presupuesto.destroyitem');



Route::get('presupuesto/pendientesbydoctor/{doctor_id}', [PresupuestoController::class, 'presupuestoByDoctor'])
->name('presupuesto.pendientesbydoctor');

Route::put('/presupuesto/update/cofirmation/{presupuesto:id}', [PresupuestoController::class, 'updateConfirmation'])
->name('presupuesto.updateConfirmation');