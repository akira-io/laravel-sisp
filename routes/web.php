<?php

declare(strict_types=1);

use Akira\Sisp\Http\Controllers\PaymentRequestController;
use Akira\Sisp\Http\Controllers\PaymentResponseController;
use Illuminate\Support\Facades\Route;

Route::get('sisp-payment-request', PaymentRequestController::class)
    ->name('sisp.payment.request');

Route::post('sisp-payment-response', PaymentResponseController::class)
    ->name('sisp.payment.response');
