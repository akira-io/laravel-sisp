<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;

function idempotentRefundTransaction(float $amount = 100.0): Transaction
{
    return Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => $amount,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
}

it('refunds a replayed partial refund only once', function (): void {
    Event::fake([TransactionRefunded::class]);
    $transaction = idempotentRefundTransaction();
    $action = resolve(RefundTransactionAction::class);

    $action->handle($transaction, 40.0, 'timeout_retry', 'refund-key-1');
    $replayed = $action->handle($transaction, 40.0, 'timeout_retry', 'refund-key-1');

    expect($transaction->refunds()->count())->toBe(1)
        ->and((int) $transaction->refunds()->sum('amount_thousandths'))->toBe(40_000)
        ->and($replayed->status)->toBe(TransactionStatus::completed)
        ->and($replayed->payload['refunds'])->toHaveCount(1)
        ->and($action->refundableAmount($replayed))->toBe(60.0);

    Event::assertDispatchedTimes(TransactionRefunded::class, 1);
});

it('answers a replayed full refund without refusing the refunded transaction', function (): void {
    Event::fake([TransactionRefunded::class]);
    $transaction = idempotentRefundTransaction();
    $action = resolve(RefundTransactionAction::class);

    $action->handle($transaction, 100.0, 'customer_request', 'refund-key-full');
    $replayed = $action->handle($transaction, 100.0, 'customer_request', 'refund-key-full');

    expect($replayed->status)->toBe(TransactionStatus::refunded)
        ->and($transaction->refunds()->count())->toBe(1);

    Event::assertDispatchedTimes(TransactionRefunded::class, 1);
});

it('refuses an idempotency key reused with a different amount', function (): void {
    Event::fake([TransactionRefunded::class]);
    $transaction = idempotentRefundTransaction();
    $action = resolve(RefundTransactionAction::class);

    $action->handle($transaction, 40.0, 'first', 'refund-key-2');

    expect(fn (): Transaction => $action->handle($transaction, 30.0, 'second', 'refund-key-2'))
        ->toThrow(LogicException::class, 'Idempotency key was already used for a refund of a different amount.')
        ->and($transaction->refunds()->count())->toBe(1)
        ->and((int) $transaction->refunds()->sum('amount_thousandths'))->toBe(40_000);

    Event::assertDispatchedTimes(TransactionRefunded::class, 1);
});

it('stores the idempotency key on the refund row', function (): void {
    $transaction = idempotentRefundTransaction();

    resolve(RefundTransactionAction::class)->handle($transaction, 40.0, 'first', 'refund-key-3');

    expect($transaction->refunds()->sole()->idempotency_key)->toBe('refund-key-3');
});

it('keeps refunding every call made without an idempotency key', function (): void {
    Event::fake([TransactionRefunded::class]);
    $transaction = idempotentRefundTransaction();
    $action = resolve(RefundTransactionAction::class);

    $action->handle($transaction, 40.0, 'first');
    $action->handle($transaction, 40.0, 'second');

    expect($transaction->refunds()->count())->toBe(2)
        ->and($transaction->refunds()->whereNull('idempotency_key')->count())->toBe(2)
        ->and($action->refundableAmount($transaction->refresh()))->toBe(20.0);

    Event::assertDispatchedTimes(TransactionRefunded::class, 2);
});

it('scopes an idempotency key to its transaction', function (): void {
    $first = idempotentRefundTransaction();
    $second = idempotentRefundTransaction();
    $action = resolve(RefundTransactionAction::class);

    $action->handle($first, 40.0, 'first', 'shared-key');
    $action->handle($second, 40.0, 'second', 'shared-key');

    expect($first->refunds()->count())->toBe(1)
        ->and($second->refunds()->count())->toBe(1);
});

it('rejects a second refund row with the same idempotency key at the database', function (): void {
    $transaction = idempotentRefundTransaction();

    $transaction->refunds()->create(['amount' => 40.0, 'idempotency_key' => 'raced-key', 'request' => []]);

    expect(fn (): Refund => $transaction->refunds()->create(['amount' => 40.0, 'idempotency_key' => 'raced-key', 'request' => []]))
        ->toThrow(UniqueConstraintViolationException::class)
        ->and($transaction->refunds()->count())->toBe(1);
});

it('mirrors the idempotency key into the payload refund history', function (): void {
    $transaction = idempotentRefundTransaction();
    $action = resolve(RefundTransactionAction::class);

    $action->handle($transaction, 40.0, 'keyed', 'refund-key-payload');
    $refunded = $action->handle($transaction, 10.0, 'unkeyed');

    expect($refunded->payload['refunds'][0]['idempotency_key'])->toBe('refund-key-payload')
        ->and($refunded->payload['refunds'][1])->not->toHaveKey('idempotency_key');
});

it('refuses a blank or oversized idempotency key before refunding', function (string $key): void {
    Event::fake([TransactionRefunded::class]);
    $transaction = idempotentRefundTransaction();

    expect(fn (): Transaction => resolve(RefundTransactionAction::class)->handle($transaction, 40.0, 'first', $key))
        ->toThrow(LogicException::class, 'Idempotency key must be a non-blank string of at most 255 characters.')
        ->and($transaction->refunds()->count())->toBe(0);

    Event::assertNotDispatched(TransactionRefunded::class);
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
    'too long' => [str_repeat('k', 256)],
]);

it('accepts an idempotency key of exactly 255 characters', function (): void {
    $transaction = idempotentRefundTransaction();
    $key = str_repeat('k', 255);

    resolve(RefundTransactionAction::class)->handle($transaction, 40.0, 'first', $key);

    expect($transaction->refunds()->sole()->idempotency_key)->toBe($key);
});
