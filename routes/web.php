<?php

declare(strict_types=1);

use Akira\Sisp\Http\Controllers\CallbackController;
use Akira\Sisp\Http\Controllers\CancelTransactionController;
use Akira\Sisp\Http\Controllers\PaymentController;
use Akira\Sisp\Http\Controllers\RefundTransactionController;
use Akira\Sisp\Http\Controllers\SandboxController;
use Illuminate\Support\Facades\Route;

Route::prefix('sisp')->name('sisp.')->group(function () {
    Route::post('/payment', [PaymentController::class])->name('payment');
    Route::post('/callback', [CallbackController::class])->name('callback')->withoutMiddleware('verify_csrf_token');
    Route::get('/fake-gateway', [SandboxController::class])->name('sandbox');
    Route::delete('/transaction/{transaction}/cancel', [CancelTransactionController::class])
        ->name('cancel');
    Route::post('/transaction/{transaction}/refund', [RefundTransactionController::class])
        ->name('refund');
});
