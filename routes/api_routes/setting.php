<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SettingGController;

Route::get('setting', [SettingGController::class, 'index'])->name('setting.index');

Route::post('setting/store', [SettingGController::class, 'settingStore'])->name('setting.settingStore');
Route::get('setting/show/{id}', [SettingGController::class, 'settingShow'])->name('setting.settingShow');
Route::post('setting/update/{id}', [SettingGController::class, 'settingUpdate'])->name('setting.settingUpdate');
Route::delete('setting/destroy/{id}', [SettingGController::class, 'settingDestroy'])->name('setting.settingDestroy');
