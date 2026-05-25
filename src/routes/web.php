<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [DashboardController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/auth', [DashboardController::class, 'index']);
});
