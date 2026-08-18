<?php

use App\Http\Controllers\API\Data\DepartmentListController;
use App\Http\Controllers\API\Data\DistrictListController;
use App\Http\Controllers\API\Data\EconomicActivityListController;
use App\Http\Controllers\API\Data\EstablishmentTypeListController;
use App\Http\Controllers\API\Data\MunicipalityListController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('data')->name('data.')->group(function () {
        Route::get('departments', DepartmentListController::class)->name('departments.index');
        Route::get('municipalities', MunicipalityListController::class)->name('municipalities.index');
        Route::get('districts', DistrictListController::class)->name('districts.index');
        Route::get('economic-activities', EconomicActivityListController::class)->name('economic-activities.index');
        Route::get('establishment-types', EstablishmentTypeListController::class)->name('establishment-types.index');
    });
});
