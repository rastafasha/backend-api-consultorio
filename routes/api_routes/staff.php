<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Staff\StaffsController;


Route::get('staffs', [StaffsController::class, 'index'])->name('staff.index');
Route::get('staff/config', [StaffsController::class, 'config'])->name('staff.config');
Route::post('staff/store', [StaffsController::class, 'store'])->name('staff.store');
Route::get('staff/show/{id}', [StaffsController::class, 'show'])->name('staff.show');
Route::post('staff/update/{role}', [StaffsController::class, 'update'])->name('staff.update');
Route::delete('staff/destroy/{id}', [StaffsController::class, 'destroy'])->name('staff.destroy');

