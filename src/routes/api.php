<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
 
Route::any('/erp-webhook', [ OrderController::class, 'confirmOrder']);

