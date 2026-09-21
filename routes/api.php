<?php

use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
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
    Route::post('/artists/{artist}/availabilities', [AvailabilityController::class, 'store']);
    Route::delete('/artists/{artist}/availabilities', [AvailabilityController::class, 'destroy']);

    Route::middleware('role:admin')->group(function () {
        Route::put('/artists/{artist}', [ArtistController::class, 'verify']);
    });
});