<?php

use App\Http\Controllers\api\mobile\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'mobile/v1/auth'], function () {
    Route::post('code', [AuthController::class, 'sendCode']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:mobile');
});
