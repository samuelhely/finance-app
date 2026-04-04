<?php

use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function() {
    Route::post('/register', [RegisteredUserController::class, 'create']);
    Route::post('/login', [SessionController::class, 'create']);

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/me', [SessionController::class, 'show']);
        Route::post('/logout', [SessionController::class, 'destroy']);
    });
});
