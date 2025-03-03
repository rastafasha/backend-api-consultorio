<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Staff\StaffsController;

Route::get('staffs', [StaffsController::class, 'index'])->name('staff.index');
Route::get('staffs/config', [StaffsController::class, 'config'])->name('staff.config');
Route::post('staffs/store', [StaffsController::class, 'store'])->name('staff.store');
Route::get('staffs/show/{id}', [StaffsController::class, 'show'])->name('staff.show');
Route::post('staffs/update/{role}', [StaffsController::class, 'update'])->name('staff.update');
Route::delete('staffs/destroy/{id}', [StaffsController::class, 'destroy'])->name('staff.destroy');
