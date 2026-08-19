<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\RegisterController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyEnvironmentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/guess.php';

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'v1'], function () {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('companies', [CompanyController::class, 'store'])
            ->name('api.v1.companies.store');
        Route::get('companies/{company}', [CompanyController::class, 'show'])
            ->name('api.v1.companies.show');
        Route::match(['put', 'patch'], 'companies/{company}', [CompanyController::class, 'update'])
            ->name('api.v1.companies.update');
        Route::get('companies/{company}/environment', [CompanyEnvironmentController::class, 'show'])
            ->name('api.v1.companies.environment.show');
        Route::match(['put', 'patch'], 'companies/{company}/environment', [CompanyEnvironmentController::class, 'update'])
            ->name('api.v1.companies.environment.update');

        Route::apiResource('users', UserController::class)->names([
            'index' => 'api.v1.users.index',
            'store' => 'api.v1.users.store',
            'show' => 'api.v1.users.show',
            'update' => 'api.v1.users.update',
            'destroy' => 'api.v1.users.destroy',
        ]);
    });
});
