<?php

declare(strict_types=1);

use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.redirect_url', '/home');
});

it('cancels the transaction when the user cancelled callback arrives', function (): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CANCEL-1',
        'merchant_session' => 'MS-CANCEL-1',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-CANCEL-1',
        'merchantSession' => 'MS-CANCEL-1',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    $transaction->refresh();

    expect($transaction->status->value)->toBe('cancelled')
        ->and($transaction->cancelled_at)->not->toBeNull()
        ->and($transaction->merchant_response)->toBe('user_cancelled');

    Event::assertDispatched(TransactionCancelled::class);
});

it('redirects without failing when the cancelled callback references an unknown transaction', function (): void {
    Event::fake([TransactionCancelled::class]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-UNKNOWN',
        'merchantSession' => 'MS-UNKNOWN',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('keeps the cancelled callback idempotent', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CANCEL-2',
        'merchant_session' => 'MS-CANCEL-2',
        'status' => 'pending',
    ]);

    $payload = [
        'merchantRef' => 'MR-CANCEL-2',
        'merchantSession' => 'MS-CANCEL-2',
        'UserCancelled' => 'true',
    ];

    $this->post(route('sisp.callback'), $payload)->assertRedirect('/home');

    $cancelledAt = $transaction->refresh()->cancelled_at;

    Event::fake([TransactionCancelled::class]);

    $this->post(route('sisp.callback'), $payload)->assertRedirect('/home');

    $transaction->refresh();

    expect($transaction->status->value)->toBe('cancelled')
        ->and($transaction->cancelled_at->toString())->toBe($cancelledAt->toString());

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('does not cancel a completed transaction when a cancelled callback arrives', function (): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->completed()->create([
        'merchant_ref' => 'MR-CANCEL-3',
        'merchant_session' => 'MS-CANCEL-3',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-CANCEL-3',
        'merchantSession' => 'MS-CANCEL-3',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    expect($transaction->refresh()->status->value)->toBe('completed');

    Event::assertNotDispatched(TransactionCancelled::class);
});
