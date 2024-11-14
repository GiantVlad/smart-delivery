<?php

declare(strict_types=1);

use App\Http\Controllers\OrderStatusController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderCreateController;

Route::get('/order', [ OrderCreateController::class, 'getOrderForm']);

Route::post('/order', [ OrderCreateController::class, 'createOrder']);

Route::get('/orders', [ OrderCreateController::class, 'getOrders']);

Route::post('/erp-webhook', [ OrderStatusController::class, 'confirmOrder']);

Route::post('/assign', [ OrderStatusController::class, 'assignCourier']);
