<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CreateTransactionAttemptAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Illuminate\Support\Facades\DB;

function makeAttemptPaymentRequest(string $merchantRef, string $merchantSession): PaymentRequest
{
    return new PaymentRequest(
        posID: 'POS1',
        merchantRef: $merchantRef,
        merchantSession: $merchantSession,
        amount: 30.0,
        currency: '132',
        is3DSec: '1',
        urlMerchantResponse: 'https://example.test/response',
        languageMessages: 'pt',
        timeStamp: '2026-01-01 01:01:01',
        fingerprintversion: '1',
        transactionCode: '1',
        fingerprint: 'fingerprint',
    );
}

it('numbers attempts sequentially per transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'R-ATTEMPT-SEQ',
        'merchant_session' => 'S-ATTEMPT-SEQ',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $action = resolve(CreateTransactionAttemptAction::class);

    $first = $action->handle($transaction, makeAttemptPaymentRequest('R-ATTEMPT-SEQ', 'S-ATTEMPT-SEQ'));
    $second = $action->handle($transaction, makeAttemptPaymentRequest('R-ATTEMPT-SEQ', 'S-ATTEMPT-SEQ-2'), supersedeCurrent: true);

    expect($first->attempt_number)->toBe(1)
        ->and($second->attempt_number)->toBe(2)
        ->and($first->refresh()->superseded_at)->not->toBeNull()
        ->and($second->superseded_at)->toBeNull();
});

it('computes the next attempt number without a row lock on the aggregate', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'R-ATTEMPT-LOCK',
        'merchant_session' => 'S-ATTEMPT-LOCK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $other = Transaction::factory()->create([
        'merchant_ref' => 'R-ATTEMPT-LOCK-2',
        'merchant_session' => 'S-ATTEMPT-LOCK-2',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    resolve(CreateTransactionAttemptAction::class)
        ->handle($transaction, makeAttemptPaymentRequest('R-ATTEMPT-LOCK', 'S-ATTEMPT-LOCK'));
    resolve(CreateTransactionAttemptAction::class)
        ->createFromTransaction($other);

    $aggregateQueries = array_values(array_filter(
        $queries,
        fn (string $sql): bool => str_contains($sql, 'max("attempt_number")'),
    ));

    expect($aggregateQueries)->toHaveCount(2);

    foreach ($aggregateQueries as $sql) {
        expect($sql)->not->toContain('for update');
    }
});
