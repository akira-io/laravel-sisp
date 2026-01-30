<?php

declare(strict_types=1);

use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Exceptions\InvalidSignatureException;
use Akira\Sisp\Facades\Sisp;
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
    // Ensure facade resolves fresh with our bindings
    Facade::clearResolvedInstances();
});

it('throws InvalidSignatureException when callback validation fails', function (): void {
    // Override Sisp facade binding to control validation
    app()->instance(Akira\Sisp\Sisp::class, new class
    {
        public function validateCallback(CallbackPayload $payload): bool
        {
            return false;
        }
    });

    Event::fake();

    $action = resolve(HandleCallbackAction::class);

    expect(fn () => $action->handle(cb_payload(SuccessMessageType::purchase->value)))
        ->toThrow(InvalidSignatureException::class);

    Event::assertNothingDispatched();

    // Verify no transaction was accessed/created
    // Since we didn't create one in setup, logic should ideally not reach creation.
    // If logic reached creation, it might create one or try to find one.
    // Given the payload has 'mref', if logic proceeded, it would try to find or create.
    // Checking count is 0 confirms we didn't get that far.
    expect(Transaction::count())->toBe(0);
});

it('dispatches events for completed, failed, and pending statuses', function (): void {
    // Override Sisp facade binding to control validation
    app()->instance(Akira\Sisp\Sisp::class, new class
    {
        public function validateCallback(CallbackPayload $payload): bool
        {
            return true;
        }
    });

    Transaction::factory()->create(['merchant_ref' => 'mref', 'merchant_session' => 'msess']);

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
