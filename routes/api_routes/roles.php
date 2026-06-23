<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Role\RolesController;

Route::resource('roles', RolesController::class);
Route::post('roles/store', [RolesController::class, 'roleStore'])->name('roles.store');
Route::get('roles/show/{role}', [RolesController::class, 'roleShow'])->name('roles.show');
Route::put('roles/update/{role}', [RolesController::class, 'roleUpdate'])->name('roles.update');
Route::delete('roles/destroy/{role}', [RolesController::class, 'roleDestroy'])->name('roles.destroy');

