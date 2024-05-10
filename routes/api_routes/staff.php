<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Staff\StaffsController;


Route::get('staffs', [StaffsController::class, 'index'])->name('index');
Route::get('staffs/config', [StaffsController::class, 'config'])->name('config');
Route::post('staffs/store', [StaffsController::class, 'store'])->name('store');
Route::get('staffs/show/{id}', [StaffsController::class, 'show'])->name('show');
Route::post('staffs/update/{role}', [StaffsController::class, 'update'])->name('update');
Route::delete('staffs/destroy/{id}', [StaffsController::class, 'destroy'])->name('destroy');

