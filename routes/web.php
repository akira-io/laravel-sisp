<?php

declare(strict_types=1);

use Akira\Sisp\Http\Controllers\CallbackController;
use Akira\Sisp\Http\Controllers\CancelTransactionController;
use Akira\Sisp\Http\Controllers\PaymentController;
use Akira\Sisp\Http\Controllers\RefundTransactionController;
use Akira\Sisp\Http\Controllers\RetryPaymentController;
use Akira\Sisp\Http\Controllers\SandboxController;
use Illuminate\Support\Facades\Route;

Route::post('sisp/payment', PaymentController::class)
    ->middleware(Akira\Sisp\Http\Middleware\ProtectPaymentRoute::class)
    ->name('sisp.payment');

Route::post('sisp/retry-payment', RetryPaymentController::class)
    ->name('sisp.retry-payment');

Route::match(['get', 'post'], 'sisp/callback', CallbackController::class)
    ->withoutMiddleware('web')
    ->middleware(Akira\Sisp\Http\Middleware\PreventDuplicateCallback::class)
    ->name('sisp.callback');

Route::post('sisp/cancel', CancelTransactionController::class)
    ->name('sisp.cancel');

Route::post('sisp/refund', RefundTransactionController::class)
    ->name('sisp.refund');

Route::match(['get', 'post'], 'sisp/sandbox', SandboxController::class)
    ->name('sisp.sandbox');
