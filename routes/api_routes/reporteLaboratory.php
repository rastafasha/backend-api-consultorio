<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RLaboratoryController;

Route::get('/rlaboratory', [RLaboratoryController::class, 'index'])->name('rlaboratory.index');

Route::get('/rlaboratory/show/{id}', [RLaboratoryController::class, 'show'])->name('rlaboratory.show');
Route::get('/rlaboratory/showByPatient/{id}', [RLaboratoryController::class, 'showByPatient'])->name('rlaboratory.showByPatient');
Route::post('/rlaboratory/add-file', [RLaboratoryController::class, 'addFiles'] )->name('rlaboratory.addFiles');
Route::post('/rlaboratory/store', [RLaboratoryController::class, 'store'])->name('rlaboratory.store');
Route::post('/rlaboratory/update/{id}', [RLaboratoryController::class, 'update'])->name('rlaboratory.update');
Route::delete('/rlaboratory/destroy/{id}', [RLaboratoryController::class, 'destroy'])->name('rlaboratory.destroy');
Route::delete('/rlaboratory/delete-file/{id}', [RLaboratoryController::class, 'removeFiles'])->name('rlaboratory.removeFiles');
