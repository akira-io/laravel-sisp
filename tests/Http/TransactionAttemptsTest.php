<?php

declare(strict_types=1);

use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\URL;

final class ConstantMerchantReferenceGenerator
{
    public function __invoke(): string
    {
        return 'MR-CONSTANT';
    }
}

final class ConstantMerchantSessionGenerator
{
    public function __invoke(): string
    {
        return 'MS-CONSTANT';
    }
}

final class IncrementingMerchantSessionGenerator
{
    private static int $count = 0;

    public function __invoke(): string
    {
        self::$count++;

        return 'MS-INCREMENTING-'.self::$count;
    }
}

beforeEach(function (): void {
    config([
        'sisp.sandbox' => true,
        'sisp.rate_limiting.enabled' => false,
        'sisp.generators.merchantReference' => Akira\Sisp\Actions\Generators\MerchantReferenceGeneratorAction::class,
        'sisp.generators.merchantSession' => Akira\Sisp\Actions\Generators\MerchantSessionGeneratorAction::class,
        'sisp.identifier_generation.max_attempts' => 5,
        'sisp.identifier_generation.collision_retry_sleep_microseconds' => 0,
    ]);
});

it('creates an auditable first attempt when a payment transaction is created', function (): void {
    $this->post(route('sisp.payment'), transaction_attempt_payment_payload())
        ->assertOk();

    $transaction = Transaction::query()->sole();
    $attempt = TransactionAttempt::query()->sole();

    expect($attempt->transaction_id)->toBe($transaction->id)
        ->and($attempt->attempt_number)->toBe(1)
        ->and($attempt->merchant_ref)->toBe($transaction->merchant_ref)
        ->and($attempt->merchant_session)->toBe($transaction->merchant_session)
        ->and($attempt->status->value)->toBe('pending')
        ->and($attempt->payload)->toBeArray();
});

it('reuses an existing pending transaction when the checkout intent is posted twice', function (): void {
    $payload = transaction_attempt_payment_payload([
        'checkout_intent_id' => 'checkout-intent-duplicate',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $transaction = Transaction::query()->sole();

    $this->post(route('sisp.payment'), $payload)
        ->assertOk()
        ->assertSee($transaction->merchant_ref);

    expect(Transaction::query()->count())->toBe(1)
        ->and(TransactionAttempt::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->sole()->transaction_id)->toBe($transaction->id);
});

it('creates a retry attempt for the same transaction when a failed checkout intent is posted again', function (): void {
    config([
        'sisp.generators.merchantSession' => IncrementingMerchantSessionGenerator::class,
    ]);

    $payload = transaction_attempt_payment_payload([
        'idempotency_key' => 'checkout-intent-retry',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $transaction = Transaction::query()->sole();
    $oldSession = $transaction->merchant_session;

    $transaction->update([
        'status' => 'failed',
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '13',
        'merchant_response' => 'declined',
        'response_code' => '13',
        'fingerprint' => 'failed-fingerprint',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect(Transaction::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->sole()->transaction_id)->toBe($transaction->id)
        ->and($attempts)->toHaveCount(2)
        ->and($attempts[0]->merchant_session)->toBe($oldSession)
        ->and($attempts[0]->superseded_at)->not->toBeNull()
        ->and($attempts[1]->merchant_ref)->toBe($transaction->merchant_ref)
        ->and($attempts[1]->merchant_session)->toBe($transaction->merchant_session)
        ->and($transaction->status->value)->toBe('pending')
        ->and($transaction->transaction_id)->toBeNull();
});

it('rejects a duplicate checkout intent while the first request is still being reserved', function (): void {
    PaymentIntent::query()->create([
        'idempotency_key' => 'checkout-intent-processing',
        'status' => 'processing',
    ]);

    $this->postJson(route('sisp.payment'), transaction_attempt_payment_payload([
        'checkout_intent_id' => 'checkout-intent-processing',
    ]))->assertConflict()
        ->assertJson([
            'message' => __('sisp::messages.validation.payment_in_progress'),
        ]);

    expect(Transaction::query()->count())->toBe(0)
        ->and(TransactionAttempt::query()->count())->toBe(0);
});

it('fails without persisting a duplicate when custom generators keep colliding', function (): void {
    config([
        'sisp.generators.merchantReference' => ConstantMerchantReferenceGenerator::class,
        'sisp.generators.merchantSession' => ConstantMerchantSessionGenerator::class,
        'sisp.identifier_generation.max_attempts' => 2,
    ]);

    $this->post(route('sisp.payment'), transaction_attempt_payment_payload())
        ->assertOk();

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('sisp.payment'), transaction_attempt_payment_payload()))
        ->toThrow(UnableToGenerateUniquePaymentIdentifiersException::class);

    expect(Transaction::query()->count())->toBe(1)
        ->and(TransactionAttempt::query()->count())->toBe(1);
});

it('creates a new attempt on retry while preserving the old session for audit', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-RETRY-ATTEMPT',
        'merchant_session' => 'MS-OLD-ATTEMPT',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    $this->post(signed_attempt_retry_url($transaction))
        ->assertOk();

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect($attempts)->toHaveCount(2)
        ->and($attempts[0]->merchant_session)->toBe('MS-OLD-ATTEMPT')
        ->and($attempts[0]->superseded_at)->not->toBeNull()
        ->and($attempts[1]->merchant_ref)->toBe('MR-RETRY-ATTEMPT')
        ->and($attempts[1]->merchant_session)->toBe($transaction->merchant_session)
        ->and($attempts[1]->superseded_at)->toBeNull();
});

it('records a late failed callback for an old attempt without overwriting the current retry attempt', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-LATE-FAILED',
        'merchant_session' => 'MS-LATE-OLD',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
    ]);

    $this->post(signed_attempt_retry_url($transaction))->assertOk();

    $transaction->refresh();
    $currentSession = $transaction->merchant_session;

    $payload = transaction_attempt_callback_payload($transaction, 'MS-LATE-OLD', 'failed');

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-LATE-FAILED']));

    $transaction->refresh();
    $oldAttempt = $transaction->attempts()->where('merchant_session', 'MS-LATE-OLD')->firstOrFail();

    expect($oldAttempt->status->value)->toBe('failed')
        ->and($oldAttempt->gateway_transaction_id)->not->toBeNull()
        ->and($transaction->status->value)->toBe('pending')
        ->and($transaction->merchant_session)->toBe($currentSession)
        ->and($transaction->transaction_id)->toBeNull();
});

it('promotes a late successful callback for an old attempt to the transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-LATE-SUCCESS',
        'merchant_session' => 'MS-LATE-SUCCESS-OLD',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_code' => '1',
    ]);

    $this->post(signed_attempt_retry_url($transaction))->assertOk();

    $payload = transaction_attempt_callback_payload($transaction, 'MS-LATE-SUCCESS-OLD', 'success');

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-LATE-SUCCESS']));

    $transaction->refresh();
    $oldAttempt = $transaction->attempts()->where('merchant_session', 'MS-LATE-SUCCESS-OLD')->firstOrFail();

    expect($oldAttempt->status->value)->toBe('completed')
        ->and($transaction->status->value)->toBe('completed')
        ->and($transaction->merchant_session)->toBe('MS-LATE-SUCCESS-OLD')
        ->and($transaction->transaction_id)->toBe($oldAttempt->gateway_transaction_id);
});

function transaction_attempt_payment_payload(array $overrides = []): array
{
    return array_replace_recursive([
        'amount' => 100.0,
        'items' => [[
            'product_name' => 'Test',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'Buyer',
        'customer_email' => 'buyer@example.test',
    ], $overrides);
}

function signed_attempt_retry_url(Transaction $transaction): string
{
    return URL::temporarySignedRoute(
        'sisp.retry-payment',
        now()->addMinutes(30),
        ['transaction' => $transaction->id],
    );
}

function transaction_attempt_callback_payload(Transaction $transaction, string $merchantSession, string $status): array
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
