<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

function refundableHistoryTransaction(): Transaction
{
    return Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
}

it('reports no refunds on a transaction that was never refunded', function (): void {
    $transaction = refundableHistoryTransaction();

    expect($transaction->refundedAmount())->toBe(0.0)
        ->and($transaction->refundableAmount())->toBe(100.0)
        ->and($transaction->isPartiallyRefunded())->toBeFalse()
        ->and($transaction->refunds)->toHaveCount(0);
});

it('aggregates successive partial refunds', function (): void {
    $transaction = refundableHistoryTransaction();
    $action = resolve(RefundTransactionAction::class);

    $action->handle($transaction, 30.0, 'first');
    $action->handle($transaction, 25.5, 'second');

    $transaction = Transaction::query()->with('refunds')->where('id', $transaction->id)->sole();

    expect($transaction->refundedAmount())->toBe(55.5)
        ->and($transaction->refundableAmount())->toBe(44.5)
        ->and($transaction->isPartiallyRefunded())->toBeTrue()
        ->and($transaction->refunds->pluck('reason')->all())->toBe(['first', 'second'])
        ->and($transaction->refundableAmount())->toBe($action->refundableAmount($transaction));
});

it('stops being partially refunded once the refunds cover the amount', function (): void {
    $transaction = refundableHistoryTransaction();
    $action = resolve(RefundTransactionAction::class);

    $transaction = $action->handle($transaction, 60.0);
    $transaction = $action->handle($transaction, 40.0);

    expect($transaction->status)->toBe(TransactionStatus::refunded)
        ->and($transaction->refundedAmount())->toBe(100.0)
        ->and($transaction->refundableAmount())->toBe(0.0)
        ->and($transaction->isPartiallyRefunded())->toBeFalse();
});

it('counts a full refund made in one step', function (): void {
    $transaction = refundableHistoryTransaction();

    $transaction = resolve(RefundTransactionAction::class)->handle($transaction, 100.0);

    expect($transaction->refundedAmount())->toBe(100.0)
        ->and($transaction->refunds)->toHaveCount(1)
        ->and($transaction->isPartiallyRefunded())->toBeFalse();
});

it('reads refunds recorded only in the payload by 2.1', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'payload' => ['refunds' => [['amount' => 20.0, 'reason' => 'legacy', 'request' => []]]],
    ]);

    expect($transaction->refundedAmount())->toBe(20.0)
        ->and($transaction->refundableAmount())->toBe(80.0)
        ->and($transaction->isPartiallyRefunded())->toBeTrue();
});

it('hands the recorded refund and the remaining balance to the event', function (): void {
    Event::fake([TransactionRefunded::class]);
    $transaction = refundableHistoryTransaction();

    resolve(RefundTransactionAction::class)->handle($transaction, 30.0, 'partial');

    Event::assertDispatched(
        TransactionRefunded::class,
        fn (TransactionRefunded $event): bool => $event->refund instanceof Refund
            && $event->refund->exists
            && $event->refund->amount === 30.0
            && $event->refund->reason === 'partial'
            && $event->remainingAmount === 70.0,
    );
});

it('still builds the event with the 2.1 arguments', function (): void {
    $event = new TransactionRefunded(refundableHistoryTransaction(), 10.0);

    expect($event->reason)->toBe('user_refund')
        ->and($event->refund)->toBeNull()
        ->and($event->remainingAmount)->toBeNull();
});
