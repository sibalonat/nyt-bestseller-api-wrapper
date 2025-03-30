<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\HealthCheckController;
use App\Http\Controllers\API\V1\BestSellerController;

Route::middleware('throttle:api')->group(function () {
    Route::get('/v1/bestsellers', [BestSellerController::class, 'index']);
});

Route::get('/health', [HealthCheckController::class, 'check']);
