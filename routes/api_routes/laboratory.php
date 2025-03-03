<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Laboratory\LaboratoryController;

Route::get('/laboratory', [LaboratoryController::class, 'index'])->name('laboratory.index');

    Route::get('/laboratory/show/{id}', [LaboratoryController::class, 'show'])->name('laboratory.show');
    Route::get('/laboratory/showByAppointment/{id}', [LaboratoryController::class, 'showByAppointment'])->name('laboratory.showByAppointment');
    Route::post('/laboratory/store', [LaboratoryController::class, 'store'])->name('laboratory.store');
    Route::post('/laboratory/update/{id}', [LaboratoryController::class, 'update'])->name('laboratory.update');
    Route::post('/laboratory/add-file', [LaboratoryController::class, 'addFiles'])->name('laboratory.addFiles');
    Route::delete('/laboratory/destroy/{id}', [LaboratoryController::class, 'destroy'])->name('laboratory.destroy');
    Route::delete('/laboratory/delete-file/{id}', [LaboratoryController::class, 'removeFiles'])->name('laboratory.removeFiles');
