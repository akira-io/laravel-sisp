<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

it('refunds a completed transaction for the full original amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 100.0, 'customer_request');

    expect($updated->status->value)->toBe('refunded')
        ->and($updated->amount)->toBe(100.0)
        ->and($updated->merchant_response)->toBe('customer_request::100');
});

it('compares decimal refund amounts using canonical thousandths', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 8.03,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 8.03, 'decimal_refund');

    expect($updated->status->value)->toBe('refunded')
        ->and($updated->amount)->toBe(8.03)
        ->and($updated->amount_cents)->toBe(803);
});

it('allows partial refund amounts and keeps the transaction completed', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 50.0, 'partial_request');

    expect($updated->status)->toBe(TransactionStatus::completed)
        ->and($updated->payload['refunds'][0]['request']['transactionCode'])->toBe('8')
        ->and($updated->payload['refunds'][0]['amount'])->toBe(50);
});

it('records refund updates with the refund log source', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    resolve(RefundTransactionAction::class)->handle($t, 50.0, 'partial_request');

    $log = $t->logs()->sole();

    expect($log->source)->toBe('refund')
        ->and($log->changed_attributes)->toContain('payload')
        ->and($log->new_values['payload'])->toBe('[redacted]');
});

it('does not allow refund amounts above the transaction amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 10.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 15.0))
        ->toThrow(LogicException::class, 'Refund amount (15) exceeds refundable balance.');
});

it('does not allow refund for non-completed status', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::pending->value,
        'amount' => 10.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 5.0))
        ->toThrow(LogicException::class);
});

it('does not allow zero or negative refund', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 10.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 0.0))
        ->toThrow(LogicException::class);
});

it('does not allow refunds above the remaining local balance', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
        'payload' => [
            'refunds' => [
                ['amount' => 75.0],
            ],
        ],
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 50.0))
        ->toThrow(LogicException::class, 'Refund amount (50) exceeds refundable balance.');
});

it('rereads the refunded balance so a stale instance cannot refund twice', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $stale = Transaction::query()->where('id', $t->id)->sole();

    resolve(RefundTransactionAction::class)->handle($t, 60.0, 'partial_request');

    expect(fn () => resolve(RefundTransactionAction::class)->handle($stale, 60.0, 'partial_request'))
        ->toThrow(LogicException::class, 'Refund amount (60) exceeds refundable balance.');
});

it('refuses a refund once the locked row is no longer completed', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    Transaction::query()->where('id', $t->id)->update(['status' => TransactionStatus::refunded->value]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 10.0))
        ->toThrow(LogicException::class, "Transaction with status 'refunded' cannot be refunded.");
});

it('updates and returns the instance the caller passed in', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $refunded = resolve(RefundTransactionAction::class)->handle($t, 100.0, 'customer_request');

    expect($refunded)->toBe($t)
        ->and($t->status)->toBe(TransactionStatus::refunded)
        ->and($t->merchant_response)->toBe('customer_request::100')
        ->and($t->isDirty())->toBeFalse();
});

it('reports the change and hands the caller\'s instance to the event', function (): void {
    Event::fake([TransactionRefunded::class]);

    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    resolve(RefundTransactionAction::class)->handle($t, 100.0, 'customer_request');

    expect($t->wasChanged('status'))->toBeTrue();

    Event::assertDispatched(
        TransactionRefunded::class,
        fn (TransactionRefunded $event): bool => $event->transaction === $t,
    );
});

it('counts a refund that reached the payload but not the refunds table', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
        'payload' => ['refunds' => [
            ['amount' => 30.0, 'reason' => 'before_migration', 'request' => []],
            ['amount' => 50.0, 'reason' => 'during_migration', 'request' => []],
        ]],
    ]);
    $t->refunds()->create(['amount' => 30.0, 'reason' => 'before_migration', 'request' => []]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 30.0))
        ->toThrow(LogicException::class, 'Refund amount (30) exceeds refundable balance.')
        ->and(resolve(RefundTransactionAction::class)->refundableAmount($t->refresh()))->toBe(20.0);
});

it('fully refunds a transaction whose parts leave less than a centavo', function (): void {
    Event::fake([TransactionRefunded::class]);
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $action = resolve(RefundTransactionAction::class);

    $action->handle($t, 33.333333);
    $action->handle($t, 33.333333);

    expect($t->status)->toBe(TransactionStatus::completed);

    $action->handle($t, 33.333333);

    expect($t->status)->toBe(TransactionStatus::refunded)
        ->and($t->refundableAmount())->toBe(0.0)
        ->and($t->isPartiallyRefunded())->toBeFalse();

    Event::assertDispatched(
        TransactionRefunded::class,
        fn (TransactionRefunded $event): bool => $event->remainingAmount === 0.0 && $event->transaction->status === TransactionStatus::refunded,
    );
});

it('keeps a transaction completed while a centavo or more is left to refund', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    resolve(RefundTransactionAction::class)->handle($t, 99.99);

    expect($t->status)->toBe(TransactionStatus::completed)
        ->and($t->refundableAmount())->toBe(0.01);
});
