<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\FindTransactionAttemptAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;

it('reuses a backfilled legacy callback attempt', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'R-LEGACY-CALLBACK',
        'merchant_session' => 'S-LEGACY-CALLBACK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $payload = new CallbackPayload(
        merchantRef: 'R-LEGACY-CALLBACK',
        merchantSession: 'S-LEGACY-CALLBACK',
        timeStamp: '20260101010101',
        amount: '30.00',
        currency: '132',
        transactionCode: '1',
        transactionID: 'TID-LEGACY-CALLBACK',
        messageType: '8',
        merchantResponse: 'OK',
        responseCode: '00',
        fingerprint: 'fingerprint',
        posID: 'POS1',
    );

    $action = resolve(FindTransactionAttemptAction::class);

    $firstAttempt = $action->handle($payload);
    $secondAttempt = $action->handle($payload);

    expect($secondAttempt->is($firstAttempt))->toBeTrue()
        ->and(TransactionAttempt::query()->count())->toBe(1)
        ->and($firstAttempt->relationLoaded('transaction'))->toBeTrue()
        ->and($firstAttempt->transaction->is($transaction))->toBeTrue()
        ->and($firstAttempt->merchant_ref)->toBe('R-LEGACY-CALLBACK')
        ->and($firstAttempt->merchant_session)->toBe('S-LEGACY-CALLBACK');
});

it('uses the latest pending local attempt for callbacks on the same SISP transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'R-RETRIED-CALLBACK',
        'merchant_session' => 'S-SISP-CALLBACK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'failed',
    ]);

    TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 1,
            'merchant_session' => 'S-SISP-CALLBACK',
            'attempt_session' => 'S-LOCAL-1',
            'status' => 'failed',
            'callback_received_at' => now(),
            'superseded_at' => now(),
        ]);

    $latestAttempt = TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 2,
            'merchant_session' => 'S-SISP-CALLBACK',
            'attempt_session' => 'S-LOCAL-2',
            'status' => 'pending',
            'callback_received_at' => null,
        ]);

    $payload = new CallbackPayload(
        merchantRef: 'R-RETRIED-CALLBACK',
        merchantSession: 'S-SISP-CALLBACK',
        timeStamp: '20260101010101',
        amount: '30.00',
        currency: '132',
        transactionCode: '1',
        transactionID: 'TID-RETRIED-CALLBACK',
        messageType: '8',
        merchantResponse: 'OK',
        responseCode: '00',
        fingerprint: 'fingerprint',
        posID: 'POS1',
    );

    $attempt = resolve(FindTransactionAttemptAction::class)->handle($payload);

    expect($attempt->is($latestAttempt))->toBeTrue();
});

it('reuses a processed superseded attempt when a duplicate callback arrives without gateway id', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'R-STALE-CALLBACK',
        'merchant_session' => 'S-SHARED-CALLBACK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'failed',
    ]);

    $processedAttempt = TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 1,
            'merchant_session' => 'S-SHARED-CALLBACK',
            'attempt_session' => 'S-LOCAL-OLD',
            'status' => 'failed',
            'fingerprint' => 'stale-fingerprint',
            'gateway_transaction_id' => null,
            'callback_received_at' => now(),
            'superseded_at' => now(),
        ]);

    TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 2,
            'merchant_session' => 'S-SHARED-CALLBACK',
            'attempt_session' => 'S-LOCAL-NEW',
            'status' => 'pending',
            'callback_received_at' => null,
        ]);

    $payload = new CallbackPayload(
        merchantRef: 'R-STALE-CALLBACK',
        merchantSession: 'S-SHARED-CALLBACK',
        timeStamp: '20260101010101',
        amount: '30.00',
        currency: '132',
        transactionCode: '1',
        transactionID: '',
        messageType: '8',
        merchantResponse: 'OK',
        responseCode: '00',
        fingerprint: 'stale-fingerprint',
        posID: 'POS1',
    );

    $attempt = resolve(FindTransactionAttemptAction::class)->handle($payload);

    expect($attempt->is($processedAttempt))->toBeTrue();
});
