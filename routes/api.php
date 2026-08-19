<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/guess.php';

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'v1'], function () {
    Route::post('login', LoginController::class)->name('login');

    Route::post('companies', [CompanyController::class, 'store'])
        ->middleware('auth:sanctum')
        ->name('api.v1.companies.store');
});
