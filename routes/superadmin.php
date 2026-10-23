<?php

use App\Http\Controllers\Superadmin\CompanyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/superadmin')
    ->name('api.v1.superadmin.')
    ->middleware(['auth:sanctum', 'user-token', 'role:superadmin'])
    ->group(function (): void {
        Route::get('companies', [CompanyController::class, 'index'])
            ->name('companies.index');

        Route::post('companies', [CompanyController::class, 'store'])
            ->name('companies.store');

        Route::get('companies/{company}', [CompanyController::class, 'show'])
            ->name('companies.show');

        Route::match(['put', 'patch'], 'companies/{company}', [CompanyController::class, 'update'])
            ->name('companies.update');
    });
