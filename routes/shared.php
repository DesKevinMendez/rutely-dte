<?php

use App\Http\Controllers\DteExportController;
use Illuminate\Support\Facades\Route;

Route::get('v1/dtes/export', DteExportController::class)
    ->middleware(['auth:sanctum', 'user-token', 'role:admin|user|superadmin'])
    ->name('api.v1.dtes.export');
