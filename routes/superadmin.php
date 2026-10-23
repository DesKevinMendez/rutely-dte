<?php

use App\Http\Controllers\Superadmin\ApiCompanyTokenController;
use App\Http\Controllers\Superadmin\CompanyController;
use App\Http\Controllers\Superadmin\CompanyUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/superadmin')
    ->name('api.v1.superadmin.')
    ->middleware(['auth:sanctum', 'user-token', 'role:superadmin'])
    ->group(function (): void {
        Route::apiResource('companies', CompanyController::class)
            ->only(['index', 'store', 'show', 'update']);

        Route::apiResource('companies.users', CompanyUserController::class)
            ->only(['index', 'store']);

        Route::apiResource('companies.tokens', ApiCompanyTokenController::class)
            ->only(['index', 'store']);

        Route::delete('companies/{company}/tokens/{token}', [ApiCompanyTokenController::class, 'destroy'])
            ->scopeBindings()
            ->name('companies.tokens.destroy');
    });
