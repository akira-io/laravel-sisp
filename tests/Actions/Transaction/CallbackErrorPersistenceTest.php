<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\FailTransactionAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\DB;

it('stores the customer message and the raw post on a refusal', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    $payload = CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'merchantRespErrorCode' => '3',
        'merchantRespErrorDescription' => 'Saldo insuficiente',
        'merchantRespAdditionalErrorMessage' => 'Saldo do cartao insuficiente',
        'unknownFieldFromSisp' => 'kept',
    ]);

    resolve(FailTransactionAction::class)->handle($transaction, $payload, 'refused');

    $transaction->refresh();

    expect($transaction->error_code)->toBe('3')
        ->and($transaction->error_message)->toBe('Saldo do cartao insuficiente')
        ->and($transaction->callback_raw_payload)
        ->toHaveKey('unknownFieldFromSisp');
});

it('prefers the screen error when no customer message is sent', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(FailTransactionAction::class)->handle($transaction, CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'merchantRespScreenError' => 'Pagamento recusado',
        'merchantRespErrorDescription' => 'Saldo insuficiente',
    ]), 'refused');

    expect($transaction->refresh()->error_message)->toBe('Pagamento recusado');
});

it('falls back to the error description as a last resort', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(FailTransactionAction::class)->handle($transaction, CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'merchantRespErrorDescription' => 'Saldo insuficiente',
    ]), 'refused');

    expect($transaction->refresh()->error_message)->toBe('Saldo insuficiente');
});

it('stores no error message when none is present', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(FailTransactionAction::class)->handle($transaction, CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
    ]), 'refused');

    expect($transaction->refresh()->error_message)->toBeNull()
        ->and($transaction->error_code)->toBeNull();
});

it('encrypts the raw post at rest', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(FailTransactionAction::class)->handle($transaction, CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'merchantRespAdditionalErrorMessage' => 'Saldo do cartao insuficiente',
    ]), 'refused');

    $stored = DB::table($transaction->getTable())
        ->where('id', $transaction->id)
        ->value('callback_raw_payload');

    expect($stored)->not->toContain('Saldo do cartao insuficiente');
});

it('masks a full PAN before persisting the raw callback post', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(FailTransactionAction::class)->handle($transaction, CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'merchantRespPan' => '4111111111111111',
    ]), 'refused');

    $transaction->refresh();

    expect($transaction->callback_raw_payload['merchantRespPan'])->toBe('************1111');

    $stored = DB::table($transaction->getTable())
        ->where('id', $transaction->id)
        ->value('callback_raw_payload');

    expect($stored)->not->toContain('4111111111111111');
});

it('leaves the raw payload unchanged when no PAN is present', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(FailTransactionAction::class)->handle($transaction, CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
    ]), 'refused');

    $transaction->refresh();

    expect($transaction->callback_raw_payload)->not->toHaveKey('merchantRespPan');
});

it('persists the error fields and masked raw payload on a successful update', function (): void {
    $transaction = Transaction::factory()->create(['status' => 'pending']);

    resolve(UpdateTransactionAction::class)->handle($transaction, CallbackPayload::from([
        'messageType' => '8',
        'merchantRespMerchantRef' => $transaction->merchant_ref,
        'merchantRespMerchantSession' => $transaction->merchant_session,
        'merchantRespPan' => '4111111111111111',
    ]));

    $transaction->refresh();

    expect($transaction->error_code)->toBeNull()
        ->and($transaction->error_message)->toBeNull()
        ->and($transaction->callback_raw_payload['merchantRespPan'])->toBe('************1111');
});
