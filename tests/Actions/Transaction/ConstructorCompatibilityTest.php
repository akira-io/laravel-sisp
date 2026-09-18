<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Actions\GetPaymentErrorResponseAction;
use Akira\Sisp\Actions\GetPaymentResponseTranslationsAction;
use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Actions\Transaction\FailTransactionAction;
use Akira\Sisp\Actions\Transaction\MapTransactionStatusAction;
use Akira\Sisp\Actions\Transaction\ShouldPropagateAttemptCallbackAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAttemptAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\InertiaAvailability;
use Akira\Sisp\ValueObjects\CallbackPayload;

it('fails a transaction built with the 2.1 constructor arguments', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);
    $action = new FailTransactionAction(
        new UpdateTransactionAttemptAction,
        resolve(ShouldPropagateAttemptCallbackAction::class),
    );

    $action->handle($transaction, CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'merchantRespErrorCode' => '3',
        'merchantRespAdditionalErrorMessage' => 'Saldo do cartao insuficiente',
    ]), 'refused');

    expect($transaction->refresh()->status->value)->toBe('failed')
        ->and($transaction->error_message)->toBe('Saldo do cartao insuficiente');
});

it('builds the update action with the 2.1 constructor arguments', function (): void {
    $action = new UpdateTransactionAction(
        resolve(MapTransactionStatusAction::class),
        new UpdateTransactionAttemptAction,
        resolve(ShouldPropagateAttemptCallbackAction::class),
    );

    expect($action)->toBeInstanceOf(UpdateTransactionAction::class);
});

it('builds the response renderer with the 2.1 constructor arguments', function (): void {
    $action = new RenderPaymentResponseAction(
        resolve(GetPaymentErrorResponseAction::class),
        resolve(GetPaymentResponseTranslationsAction::class),
        resolve(CanRetryPaymentAction::class),
        resolve(InertiaAvailability::class),
    );

    expect($action)->toBeInstanceOf(RenderPaymentResponseAction::class);
});
