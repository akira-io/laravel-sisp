<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Models\Invoice;
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

it('builds without arguments as in 2.1', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $cancelled = (new CancelTransactionAction)->handle($transaction);

    expect($cancelled->status->value)->toBe('cancelled');
});

it('updates and returns the instance the caller passed in', function (): void {
    Event::fake();

    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $cancelled = resolve(CancelTransactionAction::class)->handle($transaction, 'admin');

    expect($cancelled)->toBe($transaction)
        ->and($transaction->status->value)->toBe('cancelled')
        ->and($transaction->merchant_response)->toBe('admin')
        ->and($transaction->cancelled_at)->not->toBeNull()
        ->and($transaction->isDirty())->toBeFalse();

    Event::assertDispatched(
        Akira\Sisp\Events\TransactionCancelled::class,
        fn (Akira\Sisp\Events\TransactionCancelled $event): bool => $event->transaction === $transaction,
    );
});

it('reports the change and saves the caller\'s unsaved attributes as 2.1 did', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending', 'locale' => 'pt']);
    $transaction->locale = 'en';

    resolve(CancelTransactionAction::class)->handle($transaction);

    expect($transaction->wasChanged('status'))->toBeTrue()
        ->and($transaction->wasChanged('locale'))->toBeTrue()
        ->and($transaction->refresh()->locale)->toBe('en');
});

it('logs the old values of the locked row, not of a stale instance', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending', 'merchant_response' => 'first']);
    Transaction::query()->where('id', $transaction->id)->update(['merchant_response' => 'from the database']);

    resolve(CancelTransactionAction::class)->handle($transaction, 'admin');

    $log = $transaction->logs()->where('source', 'cancel')->sole();

    expect($log->old_values)->toMatchArray(['merchant_response' => 'from the database'])
        ->and($log->new_values)->toMatchArray(['merchant_response' => 'admin', 'status' => 'cancelled']);
});

it('cancels an invoice created after the caller loaded the relation', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);
    $transaction->load('invoice');

    $invoice = Invoice::query()->create([
        'transaction_id' => $transaction->id,
        'invoice_number' => 'INV-LATE-1',
        'invoice_date' => now(),
        'status' => 'pending',
    ]);

    resolve(CancelTransactionAction::class)->handle($transaction);

    expect($invoice->refresh()->status->value)->toBe('cancelled');
});
