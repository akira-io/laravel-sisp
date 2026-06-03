<?php

declare(strict_types=1);

use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;

function cb_payload(string $msgType): CallbackPayload
{
    return new CallbackPayload(
        merchantRef: 'mref',
        merchantSession: 'msess',
        timeStamp: '20240101010101',
        amount: '10.00',
        currency: '132',
        transactionCode: '8',
        transactionID: 'TID123',
        messageType: $msgType,
        merchantResponse: 'ok',
        responseCode: '00',
        fingerprint: 'fp',
        posID: 'POS1',
    );
}

beforeEach(function (): void {
    config()->set('sisp.posID', 'POS1');

    Facade::clearResolvedInstances();
});

it('dispatches PaymentFailed and marks the transaction failed when fingerprint is invalid', function (): void {
    app()->instance(Akira\Sisp\Sisp::class, new class
    {
        public function validateCallback(CallbackPayload $payload): bool
        {
            return false;
        }
    });

    Event::fake();
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'mref',
        'merchant_session' => 'msess',
        'amount' => 10,
        'currency' => '132',
        'transaction_code' => '8',
        'status' => TransactionStatus::pending,
    ]);

    resolve(HandleCallbackAction::class)->handle(cb_payload(SuccessMessageType::purchase->value));

    $transaction->refresh();
    expect($transaction->status->value)->toBe('failed')
        ->and($transaction->merchant_response)->toBe('invalid_callback_fingerprint');

    Event::assertDispatched(PaymentFailed::class);
});

it('dispatches events for completed, failed, and pending statuses', function (): void {
    app()->instance(Akira\Sisp\Sisp::class, new class
    {
        public function validateCallback(CallbackPayload $payload): bool
        {
            return true;
        }
    });

    Transaction::factory()->create([
        'merchant_ref' => 'mref',
        'merchant_session' => 'msess',
        'amount' => 10,
        'currency' => '132',
        'transaction_code' => '8',
    ]);

    Event::fake();
    resolve(HandleCallbackAction::class)->handle(cb_payload(SuccessMessageType::purchase->value));
    Event::assertDispatched(PaymentCompleted::class);

    Event::fake();
    resolve(HandleCallbackAction::class)->handle(cb_payload('13')); // invalidAmount -> failed
    Event::assertDispatched(PaymentFailed::class);

    Event::fake();
    resolve(HandleCallbackAction::class)->handle(cb_payload('X')); // unknown -> pending
    Event::assertDispatched(PaymentPending::class);
});
