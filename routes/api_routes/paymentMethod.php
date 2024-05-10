<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\tiposdepagoController;

//pagos
Route::get('/paymentmethods', [tiposdepagoController::class, 'index'])
    ->name('paymentmethods.index');
    
Route::get('/paymentmethods/activos', [tiposdepagoController::class, 'activos'])
    ->name('paymentmethods.activos');

Route::post('/paymentmethods/store', [tiposdepagoController::class, 'paymentStore'])
    ->name('paymentmethod.store');

Route::get('/paymentmethods/show/{tipodepago:id}', [tiposdepagoController::class, 'paymentShow'])
    ->name('paymentmethod.show');

Route::put('/paymentmethods/update/{id}', [tiposdepagoController::class, 'paymentUpdate'])
    ->name('paymentmethod.update');

Route::delete('/paymentmethods/destroy/{paymentmethod:id}', [tiposdepagoController::class, 'paymentDestroy'])
    ->name('paymentmethod.destroy');


Route::get('/paymentmethods/search/', [tiposdepagoController::class, 'search'])
    ->name('paymentmethod.search');


    Route::put('/paymentmethods/update/status/{paymentmethod:id}', [tiposdepagoController::class, 'updateStatus'])
    ->name('paymentmethod.updateStatus');


