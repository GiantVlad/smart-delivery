<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index']);

Route::middleware('auth:sanctum')->get('/auth', [DashboardController::class, 'index']);
