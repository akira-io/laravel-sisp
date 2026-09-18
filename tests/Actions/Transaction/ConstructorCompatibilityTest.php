<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Actions\GetPaymentErrorResponseAction;
use Akira\Sisp\Actions\GetPaymentResponseTranslationsAction;
use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Actions\RenderPaymentResponseBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Actions\Transaction\FailTransactionAction;
use Akira\Sisp\Actions\Transaction\MapTransactionStatusAction;
use Akira\Sisp\Actions\Transaction\ShouldPropagateAttemptCallbackAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAttemptAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Http\Controllers\CallbackController;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\InertiaAvailability;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Http\Request;

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

it('rejects an invalid fingerprint through a callback controller built with the 2.1 arguments', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $controller = new CallbackController(
        resolve(RenderPaymentResponseBasedOnConfigAction::class),
        resolve(StoreRequestMetadataAction::class),
        resolve(UpdateInvoiceStatusAction::class),
        resolve(LoadConfig::class),
    );
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $response = $controller(Request::create('/', 'POST', [
        'messageType' => '8',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'resultFingerPrint' => 'forged',
    ]));

    expect($response->getTargetUrl())->toEndWith('/home')
        ->and($transaction->refresh()->status->value)->toBe('pending');
});
