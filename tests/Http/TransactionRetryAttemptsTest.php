<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\URL;

it('renders retry with the same SISP identifiers', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-RETRY-ATTEMPT',
        'merchant_session' => 'MS-OLD-ATTEMPT',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    $this->post(signed_transaction_retry_url($transaction))
        ->assertOk();

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect($attempts)->toHaveCount(2)
        ->and($transaction->merchant_ref)->toBe('MR-RETRY-ATTEMPT')
        ->and($transaction->merchant_session)->toBe('MS-OLD-ATTEMPT')
        ->and($attempts[0]->merchant_ref)->toBe('MR-RETRY-ATTEMPT')
        ->and($attempts[0]->merchant_session)->toBe('MS-OLD-ATTEMPT')
        ->and($attempts[0]->attempt_session)->toBe('MS-OLD-ATTEMPT')
        ->and($attempts[0]->superseded_at)->not->toBeNull()
        ->and($attempts[1]->merchant_ref)->toBe('MR-RETRY-ATTEMPT')
        ->and($attempts[1]->merchant_session)->toBe('MS-OLD-ATTEMPT')
        ->and($attempts[1]->attempt_session)->not->toBe('MS-OLD-ATTEMPT')
        ->and($attempts[1]->payload['merchantSession'])->toBe('MS-OLD-ATTEMPT')
        ->and($attempts[1]->superseded_at)->toBeNull();
});

it('persists every local retry while sending the original SISP transaction identity', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-FIVE-RETRIES',
        'merchant_session' => 'MS-SISP-ORIGINAL',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '13',
    ]);

    TransactionAttempt::factory()->create([
        'transaction_id' => $transaction->id,
        'attempt_number' => 1,
        'merchant_ref' => 'MR-FIVE-RETRIES',
        'merchant_session' => 'MS-SISP-ORIGINAL',
        'attempt_session' => 'MS-SISP-ORIGINAL',
        'status' => 'failed',
        'gateway_transaction_id' => 'FAILED-GATEWAY-ID',
        'callback_received_at' => now(),
    ]);

    for ($retry = 0; $retry < 5; $retry++) {
        $this->post(signed_transaction_retry_url($transaction))->assertOk();
    }

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();
    $attemptSessions = $attempts->pluck('attempt_session')->all();

    expect($attempts)->toHaveCount(6)
        ->and($attempts->pluck('merchant_ref')->unique()->values()->all())->toBe(['MR-FIVE-RETRIES'])
        ->and($attempts->pluck('merchant_session')->unique()->values()->all())->toBe(['MS-SISP-ORIGINAL'])
        ->and(array_unique($attemptSessions))->toHaveCount(6)
        ->and($attempts->skip(1)->pluck('payload')->pluck('merchantSession')->unique()->values()->all())->toBe(['MS-SISP-ORIGINAL'])
        ->and($attempts->last()->superseded_at)->toBeNull()
        ->and($transaction->merchant_ref)->toBe('MR-FIVE-RETRIES')
        ->and($transaction->merchant_session)->toBe('MS-SISP-ORIGINAL')
        ->and($transaction->status->value)->toBe('failed')
        ->and($transaction->transaction_id)->toBe('FAILED-GATEWAY-ID');
});

it('allows a later successful callback for the same SISP transaction after a failed callback', function (): void {
    config()->set('sisp.sandbox', true);

    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-SAME-SISP-RETRY',
        'merchant_session' => 'MS-SAME-SISP-RETRY',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '13',
    ]);

    $this->post(signed_transaction_retry_url($transaction))->assertOk();

    $payload = transaction_retry_callback_payload($transaction->refresh(), 'MS-SAME-SISP-RETRY', 'success');

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-SAME-SISP-RETRY']));

    $transaction->refresh();
    $attempt = $transaction->attempts()
        ->where('merchant_session', 'MS-SAME-SISP-RETRY')
        ->orderByDesc('attempt_number')
        ->firstOrFail();

    expect($attempt->status->value)->toBe('completed')
        ->and($transaction->status->value)->toBe('completed')
        ->and($transaction->merchant_ref)->toBe('MR-SAME-SISP-RETRY')
        ->and($transaction->merchant_session)->toBe('MS-SAME-SISP-RETRY')
        ->and($transaction->transaction_id)->toBe($attempt->gateway_transaction_id);
});

function signed_transaction_retry_url(Transaction $transaction): string
{
    return URL::temporarySignedRoute(
        'sisp.retry-payment',
        now()->addMinutes(30),
        ['transaction' => $transaction->id],
    );
}

function transaction_retry_callback_payload(Transaction $transaction, string $merchantSession, string $status): array
{
    return Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => $transaction->amount,
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $merchantSession,
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => $transaction->currency,
        'transactionCode' => $transaction->transaction_code ?? '1',
    ]), $status)->toArray();
}
