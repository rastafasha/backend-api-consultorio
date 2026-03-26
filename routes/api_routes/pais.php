<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CountryController;

Route::get('paises', [CountryController::class, 'index'])->name(name: 'pais.index');
Route::get('pais/show/{id}', [CountryController::class, 'show'])->name('pais.show');
Route::get('pais/code/{code}', [CountryController::class, 'showCode'])->name('pais.showCode');

Route::get('pais/search/{request}', [CountryController::class, 'search'])
    ->name('pais.search');

// Route::post('pais/store', [PaisController::class, 'store'])->name('pais.store');
// Route::post('pais/update/{pais}', [PaisController::class, 'update'])->name('pais.update');
// Route::delete('pais/destroy/{id}', [PaisController::class, 'destroy'])->name('pais.destroy');

Route::options('/{any}', function (Request $request) {
    return response()->json(['status' => 'OK']);
    })->where('any', '.*');
