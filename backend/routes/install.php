<?php

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installation Wizard Routes  (prefix: /install, name: install.)
|--------------------------------------------------------------------------
*/

Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
Route::get('requirements', [InstallController::class, 'requirements'])->name('requirements');

Route::get('license', [InstallController::class, 'license'])->name('license');
Route::post('license', [InstallController::class, 'verifyLicense'])->name('license.verify');

Route::get('database', [InstallController::class, 'database'])->name('database');
Route::post('database', [InstallController::class, 'saveDatabase'])->name('database.save');

Route::get('settings', [InstallController::class, 'settings'])->name('settings');
Route::post('settings', [InstallController::class, 'saveSettings'])->name('settings.save');

Route::get('finished', [InstallController::class, 'finished'])->name('finished');
