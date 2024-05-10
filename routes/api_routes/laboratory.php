<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Laboratory\LaboratoryController;

Route::get('/laboratory', [LaboratoryController::class, 'index'] )->name('index');

    Route::get('/laboratory/show/{id}', [LaboratoryController::class, 'show'] )->name('show');
    Route::get('/laboratory/showByAppointment/{id}', [LaboratoryController::class, 'showByAppointment'] )->name('showByAppointment');
    Route::post('/laboratory/store', [LaboratoryController::class, 'store'] )->name('store');
    Route::post('/laboratory/update/{id}', [LaboratoryController::class, 'update'] )->name('update');
    Route::post('/laboratory/add-file', [LaboratoryController::class, 'addFiles'] )->name('addFiles');
    Route::delete('/laboratory/destroy/{id}', [LaboratoryController::class, 'destroy'] )->name('destroy');
    Route::delete('/laboratory/delete-file/{id}', [LaboratoryController::class, 'removeFiles'] )->name('removeFiles');
