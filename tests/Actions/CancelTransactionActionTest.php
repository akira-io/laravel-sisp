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
