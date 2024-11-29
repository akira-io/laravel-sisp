<?php

use Akira\Sisp\Http\Controllers\PaymentRequestController;
use Akira\Sisp\Http\Controllers\PaymentResponseController;
use Illuminate\Support\Facades\Route;

Route::get('testing', PaymentRequestController::class)->name('sisp.payment.request');

Route::post('sisp-payment-response', PaymentResponseController::class)
    ->name('sisp.payment.response');

//Route::post('payment-response', PaymentResponseController::class)->name('payment.response');
