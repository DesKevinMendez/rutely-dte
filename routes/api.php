<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\auth\RegisterController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyEnvironmentController;
use App\Http\Controllers\ContingencyEventController;
use App\Http\Controllers\ContingencyStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DteController;
use App\Http\Controllers\DteInvalidationController;
use App\Http\Controllers\MhCertificatesController;
use App\Http\Controllers\MhCredentialsController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\QueueRetryController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/guess.php';

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'user-token']);

Route::group(['prefix' => 'v1'], function () {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');

    Route::middleware(['auth:sanctum', 'user-token'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'show'])
            ->name('api.v1.dashboard.show');

        Route::get('tokens', [ApiTokenController::class, 'index'])
            ->name('api.v1.tokens.index');
        Route::post('tokens', [ApiTokenController::class, 'store'])
            ->name('api.v1.tokens.store');

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

        Route::get('mh-credentials', [MhCredentialsController::class, 'show'])
            ->name('api.v1.mh-credentials.show');
        Route::post('mh-credentials', [MhCredentialsController::class, 'store'])
            ->name('api.v1.mh-credentials.store');
        Route::get('mh-certificates', [MhCertificatesController::class, 'show'])
            ->name('api.v1.mh-certificates.show');
        Route::post('mh-certificates', [MhCertificatesController::class, 'store'])
            ->name('api.v1.mh-certificates.store');

        Route::get('dtes', [DteController::class, 'index'])
            ->name('api.v1.dtes.index');
        Route::post('dtes', [DteController::class, 'store'])
            ->name('api.v1.dtes.store');
        Route::get('dtes/{dte}', [DteController::class, 'show'])
            ->name('api.v1.dtes.show');
        Route::post('dtes/{dte}/invalidations', [DteInvalidationController::class, 'store'])
            ->name('api.v1.dtes.invalidations.store');

        Route::get('contingency', [ContingencyStatusController::class, 'show'])
            ->name('api.v1.contingency.show');
        Route::match(['put', 'patch'], 'contingency', [ContingencyStatusController::class, 'update'])
            ->name('api.v1.contingency.update');
        Route::post('contingency/events', [ContingencyEventController::class, 'store'])
            ->name('api.v1.contingency.events.store');

        Route::get('queue', [QueueController::class, 'index'])
            ->name('api.v1.queue.index');
        Route::post('queue/retries', [QueueRetryController::class, 'store'])
            ->name('api.v1.queue.retries.store');
    });
});
