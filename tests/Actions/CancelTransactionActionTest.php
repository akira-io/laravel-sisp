<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

it('cancels a pending transaction and dispatches event', function (): void {
    Event::fake();

    $t = Transaction::factory()->create([
        'status' => 'pending',
    ]);

    $updated = resolve(CancelTransactionAction::class)->handle($t, 'user_cancelled');

    expect($updated->status->value)->toBe('cancelled')
        ->and($updated->message_type)->toBe('cancelled')
        ->and($updated->merchant_response)->toBe('user_cancelled');

    Event::assertDispatched(Akira\Sisp\Events\TransactionCancelled::class);
});

it('cannot cancel completed or already cancelled transactions', function (): void {
    $completed = Transaction::factory()->create(['status' => 'completed']);
    $cancelled = Transaction::factory()->create(['status' => 'cancelled']);

    expect(fn () => resolve(CancelTransactionAction::class)->handle($completed))
        ->toThrow(LogicException::class);
    expect(fn () => resolve(CancelTransactionAction::class)->handle($cancelled))
        ->toThrow(LogicException::class);
});

it('decides on the locked row rather than the instance the caller holds', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);
    Transaction::query()->where('id', $transaction->id)->update(['status' => 'completed']);

    expect(fn () => resolve(CancelTransactionAction::class)->handle($transaction))
        ->toThrow(LogicException::class, "Transaction with status 'completed' cannot be cancelled.")
        ->and($transaction->refresh()->status->value)->toBe('completed');
});

it('still cancels failed and refunded transactions', function (string $status): void {
    Event::fake();

    $transaction = Transaction::factory()->create(['status' => $status]);

    $cancelled = resolve(CancelTransactionAction::class)->handle($transaction);

    expect($cancelled->status->value)->toBe('cancelled')
        ->and($transaction->refresh()->status->value)->toBe('cancelled');

    Event::assertDispatched(Akira\Sisp\Events\TransactionCancelled::class);
})->with(['failed', 'refunded']);
