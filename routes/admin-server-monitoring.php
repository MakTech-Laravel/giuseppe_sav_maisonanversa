<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Admin\ServerMonitoring\RestartController;
use App\Http\Controllers\Admin\ServerMonitoring\ServerMonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('servers-maintenance')
    ->name('servers-maintenance.')
    ->group(function () {
        // Read-always for any authenticated admin (parent group already uses `admin` middleware).
        Route::get('/', [ServerMonitoringController::class, 'index'])->name('index');
        Route::post('run-checks', [ServerMonitoringController::class, 'runChecks'])->name('run-checks');

        Route::middleware('permission:'.PermissionEnum::SERVERS_MAINTENANCE_RESTART->value)->group(function () {
            Route::post('settings', [ServerMonitoringController::class, 'updateSettings'])->name('settings');
            Route::post('{service}/restart', RestartController::class)
                ->whereIn('service', ['main-app', 'reverb'])
                ->name('restart');
            Route::post('restart-both', [RestartController::class, 'restartBoth'])->name('restart-both');
        });
    });
