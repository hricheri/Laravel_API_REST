<?php

use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\LikeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [ArtistController::class, 'me']);
    Route::put('/me', [ArtistController::class, 'updateMe']);

    Route::get('/artists', [ArtistController::class, 'index']);
    Route::get('/artists/{artist}/availabilities', [AvailabilityController::class, 'index']);
    Route::post('/artists/{artist}/availabilities', [AvailabilityController::class, 'store']);
    Route::delete('/artists/{artist}/availabilities', [AvailabilityController::class, 'destroy']);

    Route::get('/favorites', [LikeController::class, 'index']);

    Route::middleware('verified.artist')->group(function () {
        Route::post('/likes', [LikeController::class, 'store']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::put('/artists/{artist}', [ArtistController::class, 'verify']);
    });
});