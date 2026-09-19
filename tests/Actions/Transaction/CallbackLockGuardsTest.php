<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\FailTransactionAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;

function callback_guard_payload(Transaction $transaction, string $messageType, string $fingerprint): CallbackPayload
{
    return new CallbackPayload(
        merchantRef: $transaction->merchant_ref,
        merchantSession: $transaction->merchant_session,
        timeStamp: '20260101000000',
        amount: '10.00',
        currency: '132',
        transactionCode: '1',
        transactionID: 'TID-GUARD',
        messageType: $messageType,
        merchantResponse: $messageType === '6' ? '' : 'C',
        responseCode: '00',
        fingerprint: $fingerprint,
        posID: 'POS1',
    );
}

it('does not fail the transaction with a refusal from an attempt a retry superseded while it waited', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending', 'amount' => 10, 'currency' => '132']);
    $first = TransactionAttempt::factory()->forTransaction($transaction)->create(['attempt_number' => 1]);
    $staleFirst = TransactionAttempt::query()->where('id', $first->id)->sole();

    TransactionAttempt::query()->where('id', $first->id)->update(['superseded_at' => now()]);
    TransactionAttempt::factory()->forTransaction($transaction)->create(['attempt_number' => 2]);

    $applied = resolve(UpdateTransactionAction::class)->handle(
        $transaction,
        callback_guard_payload($transaction, '6', 'fp-late-refusal'),
        $staleFirst,
    );

    expect($applied)->toBeFalse()
        ->and($transaction->refresh()->status->value)->toBe('pending');
});

it('applies a repeated details mismatch to a pending transaction once', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending', 'amount' => 10, 'currency' => '132']);
    $attempt = TransactionAttempt::factory()->forTransaction($transaction)->create(['attempt_number' => 1]);
    $payload = callback_guard_payload($transaction, '8', 'fp-mismatch');

    $first = resolve(FailTransactionAction::class)->handle($transaction, $payload, 'callback_details_mismatch', $attempt);
    $second = resolve(FailTransactionAction::class)->handle($transaction, $payload, 'callback_details_mismatch', $attempt);

    expect($first)->toBeTrue()
        ->and($second)->toBeFalse()
        ->and($transaction->refresh()->status->value)->toBe('failed');
});
