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

it('cannot cancel a transaction in a terminal status', function (string $status): void {
    $transaction = Transaction::factory()->create(['status' => $status]);

    expect(fn () => resolve(CancelTransactionAction::class)->handle($transaction))
        ->toThrow(LogicException::class)
        ->and($transaction->refresh()->status->value)->toBe($status)
        ->and($transaction->cancelled_at)->toBeNull();
})->with(['completed', 'cancelled', 'failed', 'refunded']);

it('leaves the gateway response untouched when it refuses to cancel a failed transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_response' => 'Insufficient funds',
        'message_type' => '8',
    ]);

    expect(fn () => resolve(CancelTransactionAction::class)->handle($transaction))
        ->toThrow(LogicException::class);

    $transaction->refresh();

    expect($transaction->merchant_response)->toBe('Insufficient funds')
        ->and($transaction->message_type)->toBe('8');
});
