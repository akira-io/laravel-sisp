<?php

declare(strict_types=1);

use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Facade;

it('rolls back the attempt update when the propagated transaction update fails', function (): void {
    [$transaction, $attempt] = callback_rollback_transaction_with_conflicting_attempt();
    $payload = callback_rollback_payload($attempt, '8');

    expect(fn () => resolve(UpdateTransactionAction::class)->handle($transaction, $payload, $attempt))
        ->toThrow(QueryException::class);

    $transaction->refresh();
    $attempt->refresh();

    expect($attempt->status)->toBe(TransactionStatus::pending)
        ->and($attempt->gateway_transaction_id)->toBeNull()
        ->and($attempt->callback_payload)->toBeNull()
        ->and($transaction->merchant_ref)->toBe('MR-ATOMIC-CURRENT')
        ->and($transaction->transaction_id)->toBeNull();
});

it('rolls back the failed attempt update when the failure transaction update fails', function (): void {
    app()->instance(Akira\Sisp\Sisp::class, new class
    {
        public function validateCallback(CallbackPayload $payload): bool
        {
            return false;
        }
    });

    Facade::clearResolvedInstances();

    [$transaction, $attempt] = callback_rollback_transaction_with_conflicting_attempt();
    $payload = callback_rollback_payload($attempt, '8');

    expect(fn () => resolve(HandleCallbackAction::class)->handle($payload))
        ->toThrow(QueryException::class);

    $transaction->refresh();
    $attempt->refresh();

    expect($attempt->status)->toBe(TransactionStatus::pending)
        ->and($attempt->gateway_transaction_id)->toBeNull()
        ->and($attempt->failure_reason)->toBeNull()
        ->and($transaction->merchant_ref)->toBe('MR-ATOMIC-CURRENT')
        ->and($transaction->transaction_id)->toBeNull();
});

/**
 * @return array{0: Transaction, 1: TransactionAttempt}
 */
function callback_rollback_transaction_with_conflicting_attempt(): array
{
    Transaction::factory()->create([
        'merchant_ref' => 'MR-ATOMIC-COLLISION',
        'merchant_session' => 'MS-ATOMIC-COLLISION',
        'amount' => 10,
        'currency' => '132',
        'transaction_code' => '1',
    ]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-ATOMIC-CURRENT',
        'merchant_session' => 'MS-ATOMIC-CURRENT',
        'amount' => 10,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => TransactionStatus::pending,
    ]);

    $attempt = TransactionAttempt::factory()->create([
        'transaction_id' => $transaction->id,
        'attempt_number' => 1,
        'merchant_ref' => 'MR-ATOMIC-COLLISION',
        'merchant_session' => 'MS-ATOMIC-ATTEMPT',
        'status' => TransactionStatus::pending,
    ]);

    return [$transaction, $attempt];
}

function callback_rollback_payload(TransactionAttempt $attempt, string $messageType): CallbackPayload
{
    return new CallbackPayload(
        merchantRef: $attempt->merchant_ref,
        merchantSession: $attempt->merchant_session,
        timeStamp: '20260101010101',
        amount: '10.00',
        currency: '132',
        transactionCode: '1',
        transactionID: 'TID-ATOMIC-CALLBACK',
        messageType: $messageType,
        merchantResponse: 'OK',
        responseCode: '00',
        fingerprint: 'fingerprint',
        posID: 'POS1',
    );
}
