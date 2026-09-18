<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

it('leaves a pending transaction pending in both spellings', function (string $key): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $this->post(route('sisp.callback'), [
        $key => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ])->assertRedirect(config('sisp.redirect_url', '/'));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending)
        ->and($transaction->cancelled_at)->toBeNull();

    Event::assertNotDispatched(TransactionCancelled::class);
})->with(['userCancelled', 'UserCancelled']);

it('leaves a pending transaction pending when the cancellation arrives as a GET request', function (): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $this->get(route('sisp.callback', [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
    ]))->assertRedirect(config('sisp.redirect_url', '/'));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending)
        ->and($transaction->cancelled_at)->toBeNull();

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('leaves a pending transaction with an empty merchant session alone when the session is omitted', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_session' => '',
    ]);

    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => $transaction->merchant_ref,
    ])->assertRedirect(config('sisp.redirect_url', '/'));

    expect($transaction->refresh()->status)->toBe(TransactionStatus::pending);
});

it('redirects without a transaction when the reference is unknown', function (): void {
    $this->post(route('sisp.callback'), [
        'userCancelled' => 'true',
        'merchantRef' => 'does-not-exist',
        'merchantSession' => 'nor-this',
    ])->assertRedirect(config('sisp.redirect_url', '/'));
});
