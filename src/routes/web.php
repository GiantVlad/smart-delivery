<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderCreateController;

Route::get('/', [ DashboardController::class, 'index']);

Route::get('/order', [ OrderCreateController::class, 'getOrderForm']);

Route::post('/order', [ OrderCreateController::class, 'createOrder']);

Route::get('/orders', [ OrderCreateController::class, 'getOrders']);

Route::post('/erp-webhook', [ OrderStatusController::class, 'confirmOrder']);

Route::post('/assign', [ OrderStatusController::class, 'assignCourier']);

Route::get('/tasks', [ TaskController::class, 'getTasks']);

Route::get('/new-task', [ TaskController::class, 'createTaskForm']);

Route::post('/task', [ TaskController::class, 'createTask']);
