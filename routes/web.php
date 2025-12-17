<?php

use App\Http\Controllers\PaymentCallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Payment callback route
Route::get('/payment/callback', [PaymentCallbackController::class, 'callback'])->name('payment.callback');
