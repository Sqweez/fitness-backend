<?php

use App\Http\Controllers\Mobile\V1\AuthController;
use App\Http\Controllers\Mobile\V1\ClientController;
use App\Http\Controllers\Mobile\V1\ClubController;
use App\Http\Controllers\Mobile\V1\ConfigController;
use App\Http\Controllers\Mobile\V1\DocsController;
use App\Http\Controllers\Mobile\V1\TrainerController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'mobile/v1'], function () {
    Route::get('config', [ConfigController::class, 'show']);

    Route::group(['prefix' => 'docs'], function () {
        Route::get('openapi.yaml', [DocsController::class, 'schema']);
        Route::get('/', [DocsController::class, 'index']);
    });

    Route::group(['prefix' => 'auth'], function () {
        Route::post('code', [AuthController::class, 'sendCode']);
        Route::post('verify', [AuthController::class, 'verify']);
    });

    Route::group(['middleware' => 'auth:mobile'], function () {
        Route::get('club', [ClubController::class, 'show']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('trainers', [TrainerController::class, 'index']);

        Route::group(['prefix' => 'me'], function () {
            Route::get('/', [ClientController::class, 'me']);
            Route::group(['prefix' => 'membership'], function () {
                Route::get('/', [ClientController::class, 'membership']);
                Route::get('history', [ClientController::class, 'membershipHistory']);
            });
            Route::get('trinket', [ClientController::class, 'trinket']);
            Route::get('pass', [ClientController::class, 'pass']);
            Route::get('visits', [ClientController::class, 'visits']);
        });
    });
});
