<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/greet/{name}', [ HomeController::class, 'greet']);

Route::get('/complete/{token}', [ HomeController::class, 'greetComplete']);
