<?php

use App\Http\Controllers\Superadmin\CompanyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/superadmin')
    ->name('api.v1.superadmin.')
    ->middleware(['auth:sanctum', 'user-token', 'superadmin'])
    ->group(function (): void {
        Route::get('companies', [CompanyController::class, 'index'])
            ->name('companies.index');
    });
