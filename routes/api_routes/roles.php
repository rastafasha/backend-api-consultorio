<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Role\RolesController;

Route::resource('roles', RolesController::class);
Route::post('roles/store', [RolesController::class, 'store'])->name('store');
Route::get('roles/show/{role}', [RolesController::class, 'show'])->name('show');
Route::put('roles/update/{role}', [RolesController::class, 'update'])->name('update');
Route::delete('roles/destroy/{role}', [RolesController::class, 'destroy'])->name('destroy');

