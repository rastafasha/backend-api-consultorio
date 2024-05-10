<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminUserSoftDeletesController;

//Admin Usuarios
Route::get('/users', [AdminUserController::class, 'index'])
    ->name('users.index');

Route::get('/user/show/{user}', [AdminUserController::class, 'userShow'])
    ->name('user.show');

Route::get('/user/show/ndoc/{n_doc}', [AdminUserController::class, 'showNdoc'])
    ->name('user.showNdoc');


Route::put('/user/update/{user}', [AdminUserController::class, 'userUpdate'])
    ->name('user.update');

Route::delete('/user/destroy/{user}', [AdminUserController::class, 'userDestroy'])
    ->name('user.destroy');

Route::get('user/recientes/', [AdminUserController::class, 'recientes'])
    ->name('users.recientes');

Route::get('users/search/{request}', [AdminUserController::class, 'search'])
    ->name('users.search');

//Admin Usuarios Softdeletes
Route::get('/users/delete', [AdminUserSoftDeletesController::class, 'index'])
    ->name('users.delete.index');

Route::get('/user/delete/show/{id}', [AdminUserSoftDeletesController::class, 'userDeleteShow'])
    ->name('user.delete.show');

Route::put('/user/delete/restore/{id}', [AdminUserSoftDeletesController::class, 'userDeleteRestore'])
    ->name('user.delete.restore');

Route::delete('/user/destroy/force/{id}', [AdminUserSoftDeletesController::class, 'userDeleteforce'])
    ->name('user.delete.force');



