<?php

declare(strict_types=1);

use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Invoice;
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

    $this->travelTo('2026-09-12 10:00:00');

    $this->post(route('sisp.callback'), $payload)->assertRedirect('/home');

    $cancelledAt = $transaction->refresh()->cancelled_at;

    Event::fake([TransactionCancelled::class]);

    $this->travelTo('2026-09-12 10:05:00');

    $this->post(route('sisp.callback'), $payload)->assertRedirect('/home');

    $this->travelBack();

    $transaction->refresh();

    expect($transaction->status->value)->toBe('cancelled')
        ->and($transaction->cancelled_at->toDateTimeString())->toBe($cancelledAt->toDateTimeString())
        ->and($transaction->cancelled_at->toDateTimeString())->toBe('2026-09-12 10:00:00');

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('refuses to cancel a transaction already in a terminal status', function (string $status): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-TERMINAL',
        'merchant_session' => 'MS-TERMINAL',
        'status' => $status,
        'merchant_response' => 'gateway said so',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-TERMINAL',
        'merchantSession' => 'MS-TERMINAL',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    $transaction->refresh();

    expect($transaction->status->value)->toBe($status)
        ->and($transaction->cancelled_at)->toBeNull()
        ->and($transaction->merchant_response)->toBe('gateway said so');

    Event::assertNotDispatched(TransactionCancelled::class);
})->with(['completed', 'failed', 'refunded', 'cancelled']);

it('leaves other transactions alone when the cancelled callback names one of them', function (): void {
    $target = Transaction::factory()->create([
        'merchant_ref' => 'MR-TARGET',
        'merchant_session' => 'MS-TARGET',
        'status' => 'pending',
    ]);

    $bystander = Transaction::factory()->create([
        'merchant_ref' => 'MR-BYSTANDER',
        'merchant_session' => 'MS-BYSTANDER',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-TARGET',
        'merchantSession' => 'MS-TARGET',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    expect($target->refresh()->status->value)->toBe('cancelled')
        ->and($bystander->refresh()->status->value)->toBe('pending')
        ->and($bystander->cancelled_at)->toBeNull();
});

it('ignores a cancelled callback whose merchant session does not match', function (): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-MISMATCH',
        'merchant_session' => 'MS-MISMATCH',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-MISMATCH',
        'merchantSession' => 'MS-SOMETHING-ELSE',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    expect($transaction->refresh()->status->value)->toBe('pending')
        ->and($transaction->cancelled_at)->toBeNull();

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('cancels the invoice alongside the transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-INVOICE',
        'merchant_session' => 'MS-INVOICE',
        'status' => 'pending',
    ]);

    $invoice = Invoice::query()->create([
        'transaction_id' => $transaction->id,
        'invoice_number' => 'INV-CANCEL-1',
        'invoice_date' => now(),
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-INVOICE',
        'merchantSession' => 'MS-INVOICE',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    expect($transaction->refresh()->status->value)->toBe('cancelled')
        ->and($invoice->refresh()->status->value)->toBe('cancelled');
});

it('ignores a cancelled callback that carries no merchant session', function (): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-NO-SESSION',
        'merchant_session' => 'MS-NO-SESSION',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-NO-SESSION',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    expect($transaction->refresh()->status->value)->toBe('pending')
        ->and($transaction->cancelled_at)->toBeNull();

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('leaves the invoice untouched when it refuses to cancel a terminal transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-PAID',
        'merchant_session' => 'MS-PAID',
        'status' => 'completed',
    ]);

    $invoice = Invoice::query()->create([
        'transaction_id' => $transaction->id,
        'invoice_number' => 'INV-PAID-1',
        'invoice_date' => now(),
        'status' => 'paid',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-PAID',
        'merchantSession' => 'MS-PAID',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    expect($transaction->refresh()->status->value)->toBe('completed')
        ->and($invoice->refresh()->status->value)->toBe('paid');
});

it('rate limits repeated cancellation attempts against the same reference', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'MR-FLOOD',
        'merchant_session' => 'MS-FLOOD',
        'status' => 'pending',
    ]);

    $payload = [
        'merchantRef' => 'MR-FLOOD',
        'merchantSession' => 'MS-FLOOD',
        'UserCancelled' => 'true',
    ];

    foreach (range(1, 10) as $ignored) {
        $this->post(route('sisp.callback'), $payload)->assertRedirect('/home');
    }

    $this->post(route('sisp.callback'), $payload)->assertTooManyRequests();
});

it('does not rate limit callbacks that are not cancellations', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-NOT-LIMITED',
        'merchant_session' => 'MS-NOT-LIMITED',
        'status' => 'pending',
    ]);

    foreach (range(1, 20) as $ignored) {
        $this->get(route('sisp.callback', ['ref' => $transaction->merchant_ref]))->assertOk();
    }
});
