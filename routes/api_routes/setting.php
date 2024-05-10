<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SettingGController;

Route::resource('setting', SettingGController::class);
Route::post('setting/store', [SettingGController::class, 'store'])->name('store');
Route::get('setting/show/{id}', [SettingGController::class, 'show'])->name('show');
Route::post('setting/update/{id}', [SettingGController::class, 'update'])->name('update');
Route::delete('setting/destroy/{id}', [SettingGController::class, 'destroy'])->name('destroy');
