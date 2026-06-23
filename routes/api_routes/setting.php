<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SettingGController;

Route::resource('roles', SettingGController::class)->except([
    'store', 'show', 'update', 'destroy'
]);
Route::resource('setting', SettingGController::class);
Route::post('setting/store', [SettingGController::class, 'settingStore'])->name('setting.settingStore');
Route::get('setting/show/{id}', [SettingGController::class, 'settingShow'])->name('setting.settingShow');
Route::post('setting/update/{id}', [SettingGController::class, 'settingUpdate'])->name('setting.settingUpdate');
Route::delete('setting/destroy/{id}', [SettingGController::class, 'settingDestroy'])->name('setting.settingDestroy');
