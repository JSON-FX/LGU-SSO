<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\AuditController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\SsoController;
use App\Http\Middleware\AuditLogger;
use App\Http\Middleware\PerAppRateLimit;
use App\Http\Middleware\ValidateAppCredentials;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware(AuditLogger::class);

        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])
                ->middleware(AuditLogger::class);
            Route::post('logout-all', [AuthController::class, 'logoutAll'])
                ->middleware(AuditLogger::class);
            Route::post('refresh', [AuthController::class, 'refresh'])
                ->middleware(AuditLogger::class);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::apiResource('employees', EmployeeController::class);
        Route::prefix('employees/{employee}')->group(function () {
            Route::get('applications', [EmployeeController::class, 'applications']);
            Route::post('applications', [EmployeeController::class, 'grantAccess']);
            Route::put('applications/{application}', [EmployeeController::class, 'updateAccess']);
            Route::delete('applications/{application}', [EmployeeController::class, 'revokeAccess']);
        });

        Route::apiResource('applications', ApplicationController::class);
        Route::post('applications/{application}/regenerate-secret', [ApplicationController::class, 'regenerateSecret']);

        Route::prefix('audit')->group(function () {
            Route::get('logs', [AuditController::class, 'index']);
            Route::get('employees/{employee}/logs', [AuditController::class, 'employeeLogs']);
            Route::get('applications/{application}/logs', [AuditController::class, 'applicationLogs']);
        });
    });

    Route::prefix('sso')->middleware([ValidateAppCredentials::class, PerAppRateLimit::class])->group(function () {
        Route::post('validate', [SsoController::class, 'validate'])
            ->middleware(AuditLogger::class);
        Route::post('authorize', [SsoController::class, 'authorize'])
            ->middleware(AuditLogger::class);

        Route::middleware('auth:api')->group(function () {
            Route::get('employee', [SsoController::class, 'employee']);
        });
    });

    Route::prefix('locations')->group(function () {
        Route::get('provinces', [LocationController::class, 'provinces']);
        Route::get('provinces/{code}/cities', [LocationController::class, 'cities']);
        Route::get('cities/{code}/barangays', [LocationController::class, 'barangays']);
    });
});
