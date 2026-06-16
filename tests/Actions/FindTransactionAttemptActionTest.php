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
