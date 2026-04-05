<?php

use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Card\CardController;
use App\Http\Controllers\Api\V1\Category\CategoryController;
use App\Http\Controllers\Api\V1\Tag\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function() {
    Route::post('/register', [RegisteredUserController::class, 'create']);
    Route::post('/login', [SessionController::class, 'create']);

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/me', [SessionController::class, 'show']);
        Route::post('/logout', [SessionController::class, 'destroy']);

        Route::apiResources([
            'categories' => CategoryController::class,
            'cards' => CardController::class,
            'tags' => TagController::class,
        ]);
    });
});
