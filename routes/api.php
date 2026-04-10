<?php

use App\Http\Controllers\AI\AIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('ai')->group(function () {
    Route::post('/explain', [AIController::class, 'explain']);
    Route::post('/recommendation', [AIController::class, 'recommendation']);
});
