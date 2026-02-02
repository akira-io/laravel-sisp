<?php

declare(strict_types=1);

namespace Tests\Security;

use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\Exceptions\InvalidSignatureException;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Mockery;

beforeEach(function (): void {
    Facade::clearResolvedInstances();
});

test('security: callback processing aborts on invalid signature', function () {
    // We want to verify that:
    // 1. InvalidSignatureException is thrown.
    // 2. No database queries happen (or at least no transaction creation).
    // 3. No events are dispatched.

    Event::fake();

    $payload = new CallbackPayload(
        merchantRef: 'mref',
        merchantSession: 'msess',
        timeStamp: '20240101010101',
        amount: '10.00',
        currency: '132',
        transactionCode: '8',
        transactionID: 'TID123',
        messageType: '8',
        merchantResponse: 'ok',
        responseCode: '00',
        fingerprint: 'INVALID_FINGERPRINT',
        posID: 'POS1',
    );

    // Partial mock Sisp to fail validation
    $sispMock = Mockery::mock(Akira\Sisp\Sisp::class);
    $sispMock->shouldReceive('validateCallback')->andReturn(false);

    // We need to verify that handlePaymentCallback (or updateTransaction) is NOT called
    // But since we are calling the Action directly, we should verify that `findOrCreateTransaction`
    // (which is injected into the action) is NOT called, if we could mock it.
    // However, the action is resolved from container.

    // Let's rely on the Exception being thrown as the primary proof of abort.
    // To ensure "No database queries happen", we can mock the injected actions too.

    app()->instance(Akira\Sisp\Sisp::class, $sispMock);

    // We'll partially mock the dependencies of HandleCallbackAction to verify they aren't called.
    $findOrCreateMock = Mockery::mock(Akira\Sisp\Actions\Transaction\FindOrCreateTransactionAction::class);
    $findOrCreateMock->shouldNotReceive('handle');
    app()->instance(Akira\Sisp\Actions\Transaction\FindOrCreateTransactionAction::class, $findOrCreateMock);

    $updateTransactionMock = Mockery::mock(Akira\Sisp\Actions\Transaction\UpdateTransactionAction::class);
    $updateTransactionMock->shouldNotReceive('handle');
    app()->instance(Akira\Sisp\Actions\Transaction\UpdateTransactionAction::class, $updateTransactionMock);

    // Resolve the action (it will use our mocked dependencies)
    $action = resolve(HandleCallbackAction::class);

    // Expectation
    expect(fn () => $action->handle($payload))
        ->toThrow(InvalidSignatureException::class);
});
