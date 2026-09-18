<?php

declare(strict_types=1);

use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Contracts\CallbackFingerprintValidator;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;

function cb_payload(string $msgType, string $merchantResponse = 'C'): CallbackPayload
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
        merchantResponse: $merchantResponse,
        responseCode: '00',
        fingerprint: 'fp',
        posID: 'POS1',
    );
}

beforeEach(function (): void {
    config()->set('sisp.posID', 'POS1');

    Facade::clearResolvedInstances();
});

it('leaves the transaction untouched and dispatches nothing when the fingerprint is invalid', function (): void {
    app()->instance(CallbackFingerprintValidator::class, new class implements CallbackFingerprintValidator
    {
        public function handle(CallbackPayload $payload): bool
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
    expect($transaction->status->value)->toBe('pending')
        ->and($transaction->merchant_response)->not->toBe('invalid_callback_fingerprint');

    Event::assertNotDispatched(PaymentFailed::class);
});

it('dispatches the event matching each callback status', function (string $messageType, string $event): void {
    app()->instance(CallbackFingerprintValidator::class, new class implements CallbackFingerprintValidator
    {
        public function handle(CallbackPayload $payload): bool
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
    resolve(HandleCallbackAction::class)->handle(cb_payload($messageType));
    Event::assertDispatched($event);
})->with([
    'completed' => [SuccessMessageType::purchase->value, PaymentCompleted::class],
    'failed' => ['6', PaymentFailed::class],
    'pending' => ['X', PaymentPending::class],
]);

it('dispatches PaymentCompleted once when the same success callback arrives twice', function (): void {
    app()->instance(CallbackFingerprintValidator::class, new class implements CallbackFingerprintValidator
    {
        public function handle(CallbackPayload $payload): bool
        {
            return true;
        }
    });

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'mref',
        'merchant_session' => 'msess',
        'amount' => 10,
        'currency' => '132',
        'transaction_code' => '8',
    ]);

    Event::fake();
    resolve(HandleCallbackAction::class)->handle(cb_payload(SuccessMessageType::purchase->value));
    resolve(HandleCallbackAction::class)->handle(cb_payload(SuccessMessageType::purchase->value));

    Event::assertDispatchedTimes(PaymentCompleted::class, 1);

    expect($transaction->refresh()->status->value)->toBe('completed');
});

it('keeps a completed transaction completed when a refusal arrives afterwards', function (): void {
    app()->instance(CallbackFingerprintValidator::class, new class implements CallbackFingerprintValidator
    {
        public function handle(CallbackPayload $payload): bool
        {
            return true;
        }
    });

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'mref',
        'merchant_session' => 'msess',
        'amount' => 10,
        'currency' => '132',
        'transaction_code' => '8',
    ]);

    resolve(HandleCallbackAction::class)->handle(cb_payload(SuccessMessageType::purchase->value));

    Event::fake();
    resolve(HandleCallbackAction::class)->handle(cb_payload('6'));

    Event::assertNotDispatched(PaymentFailed::class);
    expect($transaction->refresh()->status->value)->toBe('completed');
});

it('dispatches PaymentFailed once when the same refusal arrives twice', function (): void {
    app()->instance(CallbackFingerprintValidator::class, new class implements CallbackFingerprintValidator
    {
        public function handle(CallbackPayload $payload): bool
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
    resolve(HandleCallbackAction::class)->handle(cb_payload('6'));
    resolve(HandleCallbackAction::class)->handle(cb_payload('6'));

    Event::assertDispatchedTimes(PaymentFailed::class, 1);
});
