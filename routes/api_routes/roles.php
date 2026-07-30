<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Role\RolesController;

// 🛠️ FIX DE RUTAS: Declaramos el método GET de forma explícita y directa
// Esto obliga a Laravel a ignorar cualquier recurso automático y ejecutar estrictamente tu RolesController
Route::get('roles', [RolesController::class, 'index'])->name('roles.index');

// Tus rutas POST, PUT, DELETE manuales se mantienen perfectas abajo:
Route::post('roles/store', [RolesController::class, 'roleStore'])->name('roles.store');
Route::get('roles/show/{role}', [RolesController::class, 'roleShow'])->name('roles.show');
Route::put('roles/update/{role}', [RolesController::class, 'roleUpdate'])->name('roles.update');
Route::delete('roles/destroy/{role}', [RolesController::class, 'roleDestroy'])->name('roles.destroy');
