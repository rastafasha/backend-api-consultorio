<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PubController;

Route::get('pub', [PubController::class, 'index'])->name('index');
Route::get('pub/activos', [PubController::class, 'activos'])->name('activos');
Route::post('pub/store', [PubController::class, 'store'])->name('store');
Route::get('pub/show/{id}', [PubController::class, 'show'])->name('show');
Route::post('pub/update/{id}', [PubController::class, 'update'])->name('update');
Route::delete('pub/destroy/{id}', [PubController::class, 'destroy'])->name('destroy');
Route::put('/pub/update/status/{pub:id}', [PubController::class, 'updateStatus'])
    ->name('pub.updateStatus');