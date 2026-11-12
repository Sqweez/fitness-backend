<?php

use App\Http\Controllers\Mobile\V1\AuthController;
use App\Http\Controllers\Mobile\V1\ClientController;
use App\Http\Controllers\Mobile\V1\DocsController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'mobile/v1'], function () {
    Route::group(['prefix' => 'docs'], function () {
        Route::get('openapi.yaml', [DocsController::class, 'schema']);
        Route::get('/', [DocsController::class, 'index']);
    });

    Route::group(['prefix' => 'auth'], function () {
        Route::post('code', [AuthController::class, 'sendCode']);
        Route::post('verify', [AuthController::class, 'verify']);
    });

    Route::group(['middleware' => 'auth:mobile'], function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::group(['prefix' => 'me'], function () {
            Route::get('/', [ClientController::class, 'me']);
            Route::get('membership', [ClientController::class, 'membership']);
            Route::get('trinket', [ClientController::class, 'trinket']);
            Route::get('pass', [ClientController::class, 'pass']);
        });
    });
});
