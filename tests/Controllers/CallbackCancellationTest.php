<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

it('cancels a pending transaction in both spellings', function (string $key): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $this->post(route('sisp.callback'), [
        $key => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ]);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled)
        ->and($transaction->cancelled_at)->not->toBeNull();
})->with(['userCancelled', 'UserCancelled']);

it('does not touch a completed transaction', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'completed']);

    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ]);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::completed);
});

it('ignores a cancellation whose session does not match', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => 'not-the-session',
    ]);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('redirects without a transaction when the reference is unknown', function (): void {
    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => 'does-not-exist',
        'merchantSession' => 'nor-this',
    ])->assertRedirect(config('sisp.redirect_url', '/'));
});

it('does not cancel a pending transaction with an empty merchant session when the session is omitted', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_session' => '',
    ]);

    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
    ]);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('does not touch a failed transaction', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'failed']);

    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ]);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::failed);
});

it('does not touch a refunded transaction', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'refunded']);

    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ]);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::refunded);
});

it('does not dispatch a second cancellation event when replayed against an already-cancelled transaction', function (): void {
    Event::fake();

    $transaction = Transaction::factory()->create(['status' => 'cancelled']);

    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ]);

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled);

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('cancels a pending transaction when the cancellation arrives as a GET request', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $this->get(route('sisp.callback', [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ]));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::cancelled)
        ->and($transaction->cancelled_at)->not->toBeNull();
});
