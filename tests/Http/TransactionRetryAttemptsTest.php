<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\URL;

it('renders retry with the same SISP identifiers', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::failed,
        'merchant_ref' => 'R-RETRY-ATTEMPT',
        'merchant_session' => 'S-SISP-ATTEMPT',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    $this->post(signed_transaction_retry_url($transaction))
        ->assertOk();

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect($attempts)->toHaveCount(2)
        ->and($transaction->merchant_ref)->toBe('R-RETRY-ATTEMPT')
        ->and($transaction->merchant_session)->toBe('S-SISP-ATTEMPT')
        ->and($attempts[0]->merchant_ref)->toBe('R-RETRY-ATTEMPT')
        ->and($attempts[0]->merchant_session)->toBe('S-SISP-ATTEMPT')
        ->and($attempts[0]->attempt_session)->toBe('S-SISP-ATTEMPT')
        ->and($attempts[0]->superseded_at)->not->toBeNull()
        ->and($attempts[1]->merchant_ref)->toBe('R-RETRY-ATTEMPT')
        ->and($attempts[1]->merchant_session)->toBe('S-SISP-ATTEMPT')
        ->and($attempts[1]->attempt_session)->not->toBe('S-SISP-ATTEMPT')
        ->and($attempts[1]->payload['merchantSession'])->toBe('S-SISP-ATTEMPT')
        ->and($attempts[1]->superseded_at)->toBeNull();
});

it('persists every local retry while sending the original SISP transaction identity', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::failed,
        'merchant_ref' => 'R-FIVE-RETRIES',
        'merchant_session' => 'S-SISP-ORIGINAL',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '13',
    ]);

    TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 1,
            'merchant_session' => 'S-SISP-ORIGINAL',
            'attempt_session' => 'S-SISP-ORIGINAL',
            'status' => TransactionStatus::failed,
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
        ->and($attempts->pluck('merchant_ref')->unique()->values()->all())->toBe(['R-FIVE-RETRIES'])
        ->and($attempts->pluck('merchant_session')->unique()->values()->all())->toBe(['S-SISP-ORIGINAL'])
        ->and(array_unique($attemptSessions))->toHaveCount(6)
        ->and($attempts->skip(1)->pluck('payload')->pluck('merchantSession')->unique()->values()->all())->toBe(['S-SISP-ORIGINAL'])
        ->and($attempts->last()->superseded_at)->toBeNull()
        ->and($transaction->merchant_ref)->toBe('R-FIVE-RETRIES')
        ->and($transaction->merchant_session)->toBe('S-SISP-ORIGINAL')
        ->and($transaction->status)->toBe(TransactionStatus::failed)
        ->and($transaction->transaction_id)->toBe('FAILED-GATEWAY-ID');
});

it('enforces unique local attempt sessions in the database', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::failed,
        'merchant_ref' => 'R-UNIQUE-ATTEMPT-SESSION',
        'merchant_session' => 'S-UNIQUE-SISP',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 1,
            'merchant_session' => 'S-UNIQUE-SISP',
            'attempt_session' => 'S-DUPLICATE-LOCAL',
        ]);

    expect(fn (): TransactionAttempt => TransactionAttempt::factory()
        ->forTransaction($transaction)
        ->create([
            'attempt_number' => 2,
            'merchant_session' => 'S-UNIQUE-SISP',
            'attempt_session' => 'S-DUPLICATE-LOCAL',
        ]))->toThrow(QueryException::class);
});

it('suffixes duplicate legacy attempt sessions during backfill', function (): void {
    $migration = require __DIR__.'/../../database/migrations/create_sisp_transaction_attempts_table.php';
    $method = new ReflectionMethod($migration, 'uniqueLegacyAttemptSession');
    $usedAttemptSessions = [];

    $firstAttemptSession = $method->invokeArgs($migration, ['S-LEGACY-DUPLICATE', 1, &$usedAttemptSessions]);
    $secondAttemptSession = $method->invokeArgs($migration, ['S-LEGACY-DUPLICATE', 2, &$usedAttemptSessions]);
    $activeAttemptSession = $method->invokeArgs($migration, ['S-LEGACY-DUPLICATE', '2-active', &$usedAttemptSessions]);

    expect($firstAttemptSession)->toBe('S-LEGACY-DUPLICATE')
        ->and($secondAttemptSession)->toContain('S-LEGACY-DUPLICATE-legacy-')
        ->and($secondAttemptSession)->not->toBe($firstAttemptSession)
        ->and($activeAttemptSession)->toContain('S-LEGACY-DUPLICATE-legacy-')
        ->and($activeAttemptSession)->not->toBe($secondAttemptSession);
});

it('allows a later successful callback for the same SISP transaction after a failed callback', function (): void {
    config()->set('sisp.sandbox', true);

    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::failed,
        'merchant_ref' => 'R-SAME-SISP-RETRY',
        'merchant_session' => 'S-SAME-SISP-RETRY',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '13',
    ]);

    $this->post(signed_transaction_retry_url($transaction))->assertOk();

    $payload = transaction_retry_callback_payload($transaction->refresh(), 'S-SAME-SISP-RETRY', 'success');

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => 'R-SAME-SISP-RETRY']));

    $transaction->refresh();
    $attempt = $transaction->attempts()
        ->where('merchant_session', 'S-SAME-SISP-RETRY')
        ->orderByDesc('attempt_number')
        ->firstOrFail();

    expect($attempt->status)->toBe(TransactionStatus::completed)
        ->and($transaction->status)->toBe(TransactionStatus::completed)
        ->and($transaction->merchant_ref)->toBe('R-SAME-SISP-RETRY')
        ->and($transaction->merchant_session)->toBe('S-SAME-SISP-RETRY')
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

function transaction_retry_callback_payload(Transaction $transaction, string $merchantSession, string $status): CallbackPayload
{
    return Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => $transaction->amount,
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $merchantSession,
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => $transaction->currency,
        'transactionCode' => $transaction->transaction_code ?? '1',
    ]), $status);
}
