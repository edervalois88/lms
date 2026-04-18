<?php

use App\Http\Controllers\AI\AIController;
use App\Http\Controllers\Rewards\RewardStoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'throttle:30,1'])->prefix('ai')->group(function () {
    Route::post('/explain', [AIController::class, 'explain']);
    Route::post('/recommendation', [AIController::class, 'recommendation']);
});

Route::middleware('auth:sanctum')->prefix('gamification')->group(function () {
    Route::get('/state', [RewardStoreController::class, 'state']);
});
