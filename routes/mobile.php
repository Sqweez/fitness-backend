<?php

use App\Http\Controllers\api\mobile\v1\AuthController;
use App\Http\Controllers\api\mobile\v1\DocsController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'mobile/v1'], function () {
    Route::get('docs/openapi.yaml', [DocsController::class, 'schema']);
    Route::get('docs', [DocsController::class, 'index']);

    Route::group(['prefix' => 'auth'], function () {
        Route::post('code', [AuthController::class, 'sendCode']);
        Route::post('verify', [AuthController::class, 'verify']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:mobile');
    });
});
