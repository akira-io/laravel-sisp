<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RetryPaymentAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;

beforeEach(function (): void {
    $this->action = resolve(RetryPaymentAction::class);
});

it('extracts data and returns payment request', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 10000.0,
        'merchant_ref' => 'REF123456',
        'merchant_session' => 'SESS123456',
        'currency' => '132',
        'transaction_code' => '1',
    ]);

    $paymentRequest = $this->action->handle($transaction);

    expect($paymentRequest)->toBeInstanceOf(PaymentRequest::class)
        ->and($paymentRequest->amount)->toBe(10000.0);
});

it('includes all transaction details', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 25000.0,
        'merchant_ref' => 'CUSTOM-REF-123',
        'merchant_session' => 'CUSTOM-SESS-456',
        'currency' => 'CVE',
        'transaction_code' => '1',
    ]);

    $paymentRequest = $this->action->handle($transaction);

    expect($paymentRequest->merchantRef)->toBe('CUSTOM-REF-123')
        ->and($paymentRequest->merchantSession)->not->toBe('CUSTOM-SESS-456')
        ->and($paymentRequest->merchantSession)->not->toBe('')
        ->and($paymentRequest->currency)->toBe('CVE');
});

it('creates valid payment request with data from transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 50000.0,
        'merchant_ref' => 'REF-789',
        'merchant_session' => 'SESS-789',
        'currency' => 'CVE',
        'transaction_code' => '1',
    ]);

    $paymentRequest = $this->action->handle($transaction);

    expect($paymentRequest->amount)->toBe(50000.0)
        ->and($paymentRequest->transactionCode)->toBe('1');
});
