<?php

use Akira\Sisp\Http\Controllers\PaymentRequestController;
use Akira\Sisp\Http\Controllers\PaymentResponseController;
use Illuminate\Support\Facades\Route;

Route::get('testing', PaymentRequestController::class)->name('payment.request');
Route::post('sisp-payment-response', PaymentResponseController::class)
    ->name('payment.response');

//Route::post('payment-response', PaymentResponseController::class)->name('payment.response');
