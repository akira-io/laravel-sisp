<?php

declare(strict_types=1);

use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;

function cb_payload_security(string $msgType): CallbackPayload
{
    return new CallbackPayload(
        merchantRef: 'mref_sec',
        merchantSession: 'msess_sec',
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
    Facade::clearResolvedInstances();
});

it('does not update transaction status when signature is invalid', function (): void {
    // 1. Setup invalid signature mock
    // We only implement validateCallback as that is the only method called on the facade in this flow
    app()->instance(Akira\Sisp\Sisp::class, new class
    {
        public function validateCallback(CallbackPayload $payload): bool
        {
            return false;
        }
    });

    // 2. Create pending transaction
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'mref_sec',
        'merchant_session' => 'msess_sec',
        'status' => TransactionStatus::pending,
    ]);

    Event::fake();

    // 3. Attempt to handle callback with success message
    $action = resolve(HandleCallbackAction::class);

    $action->handle(cb_payload_security(SuccessMessageType::purchase->value));

    // 4. Assert transaction is still pending
    $transaction->refresh();

    // In the VULNERABLE version, the transaction is updated to completed before validation
    expect($transaction->status)->toBe(TransactionStatus::pending);

    // Also assert PaymentFailed was dispatched
    Event::assertDispatched(PaymentFailed::class);
});
