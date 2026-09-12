<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

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
