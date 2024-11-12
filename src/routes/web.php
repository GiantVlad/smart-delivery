<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/order', [ OrderController::class, 'getOrderForm']);

Route::post('/order', [ OrderController::class, 'createOrder']);

Route::any('/erp-web-hook', [ OrderController::class, 'confirmOrder']);
