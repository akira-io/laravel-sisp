<?php

declare(strict_types=1);

use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\URL;

it('does not let a late failed callback from a superseded attempt overwrite the current retry', function (): void {
    config()->set('sisp.sandbox', true);

    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::failed,
        'merchant_ref' => 'R-LATE-FAILED-CALLBACK',
        'merchant_session' => 'S-LATE-FAILED-CALLBACK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '6',
    ]);

    TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 1,
            'merchant_session' => 'S-LATE-FAILED-CALLBACK',
            'attempt_session' => 'S-LATE-FAILED-CALLBACK',
            'status' => TransactionStatus::failed,
            'gateway_transaction_id' => 'FAILED-GATEWAY-ID',
            'message_type' => '6',
            'callback_received_at' => now(),
        ]);

    $this->post(late_callback_signed_transaction_retry_url($transaction))->assertOk();

    $payload = late_callback_payload(
        $transaction->refresh(),
        'S-LATE-FAILED-CALLBACK',
        'failed',
        'FAILED-GATEWAY-ID',
    );

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => 'R-LATE-FAILED-CALLBACK']));

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect($attempts)->toHaveCount(2)
        ->and($attempts[0]->status)->toBe(TransactionStatus::failed)
        ->and($attempts[0]->superseded_at)->not->toBeNull()
        ->and($attempts[1]->status)->toBe(TransactionStatus::pending)
        ->and($attempts[1]->callback_received_at)->toBeNull()
        ->and($attempts[1]->superseded_at)->toBeNull()
        ->and($transaction->status)->toBe(TransactionStatus::failed)
        ->and($transaction->transaction_id)->toBe('FAILED-GATEWAY-ID');
});

it('promotes the transaction when a late successful callback belongs to a superseded attempt', function (): void {
    config()->set('sisp.sandbox', true);

    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::failed,
        'merchant_ref' => 'R-LATE-SUCCESS-CALLBACK',
        'merchant_session' => 'S-LATE-SUCCESS-CALLBACK',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '6',
    ]);

    TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 1,
            'merchant_session' => 'S-LATE-SUCCESS-CALLBACK',
            'attempt_session' => 'S-LATE-SUCCESS-CALLBACK',
            'status' => TransactionStatus::failed,
            'gateway_transaction_id' => 'FAILED-GATEWAY-ID',
            'message_type' => '6',
            'callback_received_at' => now(),
        ]);

    $this->post(late_callback_signed_transaction_retry_url($transaction))->assertOk();

    $payload = late_callback_payload(
        $transaction->refresh(),
        'S-LATE-SUCCESS-CALLBACK',
        'success',
        'FAILED-GATEWAY-ID',
    );

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => 'R-LATE-SUCCESS-CALLBACK']));

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect($attempts)->toHaveCount(2)
        ->and($attempts[0]->status)->toBe(TransactionStatus::completed)
        ->and($attempts[0]->superseded_at)->not->toBeNull()
        ->and($attempts[1]->status)->toBe(TransactionStatus::pending)
        ->and($attempts[1]->callback_received_at)->toBeNull()
        ->and($transaction->status)->toBe(TransactionStatus::completed)
        ->and($transaction->transaction_id)->toBe('FAILED-GATEWAY-ID');
});

function late_callback_signed_transaction_retry_url(Transaction $transaction): string
{
    return URL::temporarySignedRoute(
        'sisp.retry-payment',
        now()->addMinutes(30),
        ['transaction' => $transaction->id],
    );
}

function late_callback_payload(
    Transaction $transaction,
    string $merchantSession,
    string $status,
    string $gatewayTransactionId,
): CallbackPayload {
    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => $transaction->amount,
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $merchantSession,
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => $transaction->currency,
        'transactionCode' => $transaction->transaction_code ?? '1',
    ]), $status);

    $payloadData = $payload->toArray();
    $payloadData['merchantRespTid'] = $gatewayTransactionId;
    $payload = CallbackPayload::from($payloadData);
    $payloadData['resultFingerPrint'] = resolve(PaymentResponseFingerPrintAction::class)->handle($payload);

    return CallbackPayload::from($payloadData);
}
