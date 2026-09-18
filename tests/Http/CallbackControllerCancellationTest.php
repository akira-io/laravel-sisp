<?php

declare(strict_types=1);

use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.redirect_url', '/home');
});

function recordCancellationLogs(): ArrayObject
{
    $messages = new ArrayObject;

    Log::listen(function (MessageLogged $event) use ($messages): void {
        $messages->append($event);
    });

    return $messages;
}

it('leaves the transaction pending when the user cancelled callback arrives', function (): void {
    Event::fake([TransactionCancelled::class]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CANCEL-1',
        'merchant_session' => 'MS-CANCEL-1',
        'status' => 'pending',
        'merchant_response' => null,
        'message_type' => null,
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-CANCEL-1',
        'merchantSession' => 'MS-CANCEL-1',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    $transaction->refresh();

    expect($transaction->status->value)->toBe('pending')
        ->and($transaction->cancelled_at)->toBeNull()
        ->and($transaction->message_type)->toBeNull()
        ->and($transaction->merchant_response)->toBeNull();

    Event::assertNotDispatched(TransactionCancelled::class);
});

it('logs the customer cancellation against the pending transaction', function (): void {
    $messages = recordCancellationLogs();

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CANCEL-LOG',
        'merchant_session' => 'MS-CANCEL-LOG',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-CANCEL-LOG',
        'merchantSession' => 'MS-CANCEL-LOG',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    $logged = collect($messages->getArrayCopy())
        ->first(fn (MessageLogged $event): bool => $event->message === 'SISP callback reported that the customer cancelled the payment.');

    expect($logged)->toBeInstanceOf(MessageLogged::class)
        ->and($logged->level)->toBe('info')
        ->and($logged->context)->toBe([
            'transaction_id' => $transaction->id,
            'merchant_ref' => 'MR-CANCEL-LOG',
        ]);
});

it('logs nothing when the cancelled callback does not name a pending transaction', function (array $payload): void {
    $messages = recordCancellationLogs();

    Transaction::factory()->create([
        'merchant_ref' => 'MR-NOT-LOGGED',
        'merchant_session' => 'MS-NOT-LOGGED',
        'status' => 'completed',
    ]);

    $this->post(route('sisp.callback'), [...$payload, 'UserCancelled' => 'true'])->assertRedirect('/home');

    expect($messages)->toHaveCount(0);
})->with([
    'unknown reference' => [['merchantRef' => 'MR-UNKNOWN', 'merchantSession' => 'MS-UNKNOWN']],
    'terminal transaction' => [['merchantRef' => 'MR-NOT-LOGGED', 'merchantSession' => 'MS-NOT-LOGGED']],
    'empty reference' => [['merchantRef' => '', 'merchantSession' => 'MS-NOT-LOGGED']],
    'no session' => [['merchantRef' => 'MR-NOT-LOGGED']],
]);

it('leaves a transaction in any status untouched', function (string $status): void {
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
})->with(['pending', 'completed', 'failed', 'refunded', 'cancelled']);

it('leaves the invoice pending', function (): void {
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

    expect($transaction->refresh()->status->value)->toBe('pending')
        ->and($invoice->refresh()->status->value)->toBe('pending');
});

it('keeps the transaction eligible for sisp:expire-pending', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-EXPIRE',
        'merchant_session' => 'MS-EXPIRE',
        'status' => 'pending',
        'message_type' => null,
        'created_at' => now()->subDays(31),
    ]);

    $this->post(route('sisp.callback'), [
        'merchantRef' => 'MR-EXPIRE',
        'merchantSession' => 'MS-EXPIRE',
        'UserCancelled' => 'true',
    ])->assertRedirect('/home');

    $this->artisan('sisp:expire-pending')->assertSuccessful();

    expect($transaction->refresh()->status->value)->toBe('cancelled')
        ->and($transaction->merchant_response)->toBe('expired');
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
