<?php

declare(strict_types=1);

use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderCreateController;

Route::get('/order', [ OrderCreateController::class, 'getOrderForm']);

Route::post('/order', [ OrderCreateController::class, 'createOrder']);

Route::post('update-order-status-in-task', [ OrderStatusController::class, 'updateStatusByCourier']);

Route::get('/orders', [ OrderCreateController::class, 'getOrders']);

Route::get('/orders-by-task/{taskUuid}', [ OrderCreateController::class, 'getOrdersByTask']);

Route::post('/erp-webhook', [ OrderStatusController::class, 'confirmOrder']);

Route::post('/assign', [ OrderStatusController::class, 'assignCourier']);

Route::get('/tasks', [ TaskController::class, 'getTasks']);

Route::get('/task', [ TaskController::class, 'createTaskForm']);

Route::post('/task', [ TaskController::class, 'createTask']);

Route::post('/update-route', [ RouteController::class, 'updateRoute']);

Route::get('/route/{taskUuid}', [ RouteController::class, 'getRoute']);
