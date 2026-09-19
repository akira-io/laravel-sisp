<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\FailTransactionAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;

function callback_once_payload(Transaction $transaction, string $messageType, string $fingerprint, string $transactionId = 'TID-1'): CallbackPayload
{
    return new CallbackPayload(
        merchantRef: $transaction->merchant_ref,
        merchantSession: $transaction->merchant_session,
        timeStamp: '20260101000000',
        amount: '10.00',
        currency: '132',
        transactionCode: '1',
        transactionID: $transactionId,
        messageType: $messageType,
        merchantResponse: $messageType === '6' ? '' : 'C',
        responseCode: '00',
        fingerprint: $fingerprint,
        posID: 'POS1',
    );
}

/**
 * @return array{0: Transaction, 1: TransactionAttempt}
 */
function callback_once_transaction(string $status = 'pending'): array
{
    $transaction = Transaction::factory()->create(['status' => $status, 'amount' => 10, 'currency' => '132']);
    $attempt = TransactionAttempt::factory()->forTransaction($transaction)->create(['attempt_number' => 1]);

    return [$transaction, $attempt];
}

it('writes nothing when stale instances replay a callback another request already applied', function (): void {
    [$transaction, $attempt] = callback_once_transaction();
    $staleTransaction = Transaction::query()->where('id', $transaction->id)->sole();
    $staleAttempt = TransactionAttempt::query()->where('id', $attempt->id)->sole();
    $payload = callback_once_payload($transaction, '8', 'fp-success');

    expect(resolve(UpdateTransactionAction::class)->handle($transaction, $payload, $attempt))->toBeTrue();

    $updatedAt = $transaction->refresh()->updated_at;
    $this->travel(5)->minutes();

    expect(resolve(UpdateTransactionAction::class)->handle($staleTransaction, $payload, $staleAttempt))->toBeFalse()
        ->and($transaction->refresh()->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and($staleTransaction->status->value)->toBe('completed');
});

it('keeps a completed transaction when a refusal with another fingerprint arrives', function (): void {
    [$transaction, $attempt] = callback_once_transaction();
    resolve(UpdateTransactionAction::class)->handle($transaction, callback_once_payload($transaction, '8', 'fp-success'), $attempt);

    $applied = resolve(UpdateTransactionAction::class)->handle($transaction, callback_once_payload($transaction, '6', 'fp-refusal'), $attempt);

    expect($applied)->toBeFalse()
        ->and($transaction->refresh()->status->value)->toBe('completed');
});

it('keeps a refunded transaction refunded when a success arrives again', function (): void {
    [$transaction, $attempt] = callback_once_transaction('refunded');

    $applied = resolve(UpdateTransactionAction::class)->handle($transaction, callback_once_payload($transaction, '8', 'fp-late'), $attempt);

    expect($applied)->toBeFalse()
        ->and($transaction->refresh()->status->value)->toBe('refunded');
});

it('completes a cancelled transaction whose payment SISP confirms afterwards', function (): void {
    [$transaction, $attempt] = callback_once_transaction('cancelled');

    $applied = resolve(UpdateTransactionAction::class)->handle($transaction, callback_once_payload($transaction, '8', 'fp-after-cancel'), $attempt);

    expect($applied)->toBeTrue()
        ->and($transaction->refresh()->status->value)->toBe('completed');
});

it('records a second paid attempt on its row without reopening the completed transaction', function (): void {
    [$transaction, $first] = callback_once_transaction();
    $second = TransactionAttempt::factory()->forTransaction($transaction)->create(['attempt_number' => 2]);
    resolve(UpdateTransactionAction::class)->handle($transaction, callback_once_payload($transaction, '8', 'fp-first', 'TID-1'), $first);

    $applied = resolve(UpdateTransactionAction::class)->handle($transaction, callback_once_payload($transaction, '8', 'fp-second', 'TID-2'), $second);

    expect($applied)->toBeFalse()
        ->and($second->refresh()->gateway_transaction_id)->toBe('TID-2')
        ->and($second->callback_received_at)->not->toBeNull()
        ->and($transaction->refresh()->transaction_id)->toBe('TID-1');
});

it('does not fail a completed transaction on a details mismatch', function (): void {
    [$transaction, $attempt] = callback_once_transaction();
    resolve(UpdateTransactionAction::class)->handle($transaction, callback_once_payload($transaction, '8', 'fp-success'), $attempt);

    $applied = resolve(FailTransactionAction::class)->handle(
        $transaction,
        callback_once_payload($transaction, '8', 'fp-mismatch'),
        'callback_details_mismatch',
        $attempt,
    );

    expect($applied)->toBeFalse()
        ->and($transaction->refresh()->status->value)->toBe('completed')
        ->and($transaction->merchant_response)->not->toBe('callback_details_mismatch');
});
