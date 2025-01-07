<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CentrifugoController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderCreateController;

Route::post('login', [ AuthController::class, 'login' ])->name('api.login');

Route::post('register', [ AuthController::class, 'register' ])->name('api.register');

Route::get('users', [ UserController::class, 'list' ])->name('api.users');

Route::get('/order', [ OrderCreateController::class, 'getOrderForm' ]);

Route::post('/order', [ OrderCreateController::class, 'createOrder' ]);

Route::post('update-order-status-in-task', [ OrderStatusController::class, 'updateStatusByCourier' ]);

Route::get('/orders', [ OrderController::class, 'getOrders' ]);

Route::get('/orders-to-assign', [ OrderController::class, 'getOrdersToAssign' ]);

Route::get('/orders-by-task/{taskUuid}', [ OrderCreateController::class, 'getOrdersByTask' ]);

Route::post('/erp-webhook', [ OrderStatusController::class, 'confirmOrder' ]);

Route::get('/tasks', [ TaskController::class, 'getTasks' ]);

Route::get('/task', [ TaskController::class, 'createTaskForm' ]);

Route::post('/task', [ TaskController::class, 'createTask' ]);

Route::post('/unassign-order', [ OrderController::class, 'unassignOrder' ]);

Route::post('/add-orders-to-task', [ OrderController::class, 'addOrdersToTask' ]);

Route::post('/update-route', [ RouteController::class, 'updateRoute' ]);

Route::get('/route/{taskUuid}', [ RouteController::class, 'getRoute' ]);

Route::get('/couriers/{statuses?}', [ CourierController::class, 'get' ]);

Route::get('/courier/{uuid}', [ CourierController::class, 'getCourier' ]);

Route::post('/update-courier', [ CourierController::class, 'updateCourier' ]);

Route::post('create-courier', [ CourierController::class, 'createCourier' ]);

Route::post('create-customer', [ CustomerController::class, 'createCustomer' ]);

Route::get('customers/{limit?}', [ CustomerController::class, 'get' ]);

Route::get('/centrifugo/connection-token', [CentrifugoController::class, 'getConnectionToken']);
Route::post('/centrifugo/subscription-token', [CentrifugoController::class, 'getSubscriptionToken']);
