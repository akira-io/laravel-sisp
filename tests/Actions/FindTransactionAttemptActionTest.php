<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\FindTransactionAttemptAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;

it('reuses a backfilled legacy callback attempt', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'MR-LEGACY-CALLBACK',
        'merchant_session' => 'MS-LEGACY-CALLBACK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $payload = new CallbackPayload(
        merchantRef: 'MR-LEGACY-CALLBACK',
        merchantSession: 'MS-LEGACY-CALLBACK',
        timeStamp: '20240101010101',
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
        ->and($firstAttempt->merchant_ref)->toBe('MR-LEGACY-CALLBACK')
        ->and($firstAttempt->merchant_session)->toBe('MS-LEGACY-CALLBACK');
});

it('uses the latest pending local attempt for callbacks on the same SISP transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-RETRIED-CALLBACK',
        'merchant_session' => 'MS-SISP-CALLBACK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'failed',
    ]);

    TransactionAttempt::factory()->create([
        'transaction_id' => $transaction->id,
        'attempt_number' => 1,
        'merchant_ref' => 'MR-RETRIED-CALLBACK',
        'merchant_session' => 'MS-SISP-CALLBACK',
        'attempt_session' => 'MS-LOCAL-1',
        'status' => 'failed',
        'callback_received_at' => now(),
        'superseded_at' => now(),
    ]);

    $latestAttempt = TransactionAttempt::factory()->create([
        'transaction_id' => $transaction->id,
        'attempt_number' => 2,
        'merchant_ref' => 'MR-RETRIED-CALLBACK',
        'merchant_session' => 'MS-SISP-CALLBACK',
        'attempt_session' => 'MS-LOCAL-2',
        'status' => 'pending',
        'callback_received_at' => null,
    ]);

    $payload = new CallbackPayload(
        merchantRef: 'MR-RETRIED-CALLBACK',
        merchantSession: 'MS-SISP-CALLBACK',
        timeStamp: '20240101010101',
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
