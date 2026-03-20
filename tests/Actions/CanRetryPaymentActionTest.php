<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Models\Transaction;

it('allows retry when 3DS is disabled and retry is enabled', function (): void {
    config([
        'sisp.allow_retry' => true,
        'sisp.is_3dsec' => '0',
    ]);

    $transaction = Transaction::factory()->failed()->create();

    $canRetry = resolve(CanRetryPaymentAction::class)->handle($transaction);

    expect($canRetry)->toBeTrue();
});

it('blocks retry when 3DS is enabled and required customer data is missing', function (): void {
    config([
        'sisp.allow_retry' => true,
        'sisp.is_3dsec' => '1',
    ]);

    $transaction = Transaction::factory()->failed()->create([
        'customer_email' => null,
        'customer_country' => null,
        'customer_city' => null,
        'customer_address' => null,
    ]);

    $canRetry = resolve(CanRetryPaymentAction::class)->handle($transaction);

    expect($canRetry)->toBeFalse();
});

it('allows retry when 3DS is enabled and required customer data exists', function (): void {
    config([
        'sisp.allow_retry' => true,
        'sisp.is_3dsec' => '1',
    ]);

    $transaction = Transaction::factory()->failed()->create([
        'customer_email' => 'john@example.test',
        'customer_country' => 'cv',
        'customer_city' => 'Praia',
        'customer_address' => 'Rua Principal',
    ]);

    $canRetry = resolve(CanRetryPaymentAction::class)->handle($transaction);

    expect($canRetry)->toBeTrue();
});

it('blocks retry when retry is disabled by configuration', function (): void {
    config([
        'sisp.allow_retry' => false,
        'sisp.is_3dsec' => '0',
    ]);

    $transaction = Transaction::factory()->failed()->create();

    $canRetry = resolve(CanRetryPaymentAction::class)->handle($transaction);

    expect($canRetry)->toBeFalse();
});
