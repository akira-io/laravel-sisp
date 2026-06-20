<?php

declare(strict_types=1);

use Akira\Sisp\Contracts\Generator;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;

final readonly class ConstantMerchantReferenceGenerator implements Generator
{
    public function __invoke(): string
    {
        return 'R-COLLISION';
    }
}

final readonly class ConstantMerchantSessionGenerator implements Generator
{
    public function __invoke(): string
    {
        return 'S-COLLISION';
    }
}

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.rate_limiting.enabled', false);
    config()->set('sisp.identifier_generation.max_attempts', 2);
    config()->set('sisp.identifier_generation.collision_retry_sleep_microseconds', 0);
});

it('creates the initial attempt when a payment transaction is stored', function (): void {
    $this->post(route('sisp.payment'), transaction_attempts_payment_payload())
        ->assertOk();

    $transaction = Transaction::query()->with('attempts')->sole();

    expect($transaction->attempts)->toHaveCount(1)
        ->and($transaction->currentAttempt)->toBeInstanceOf(TransactionAttempt::class)
        ->and($transaction->currentAttempt->merchant_ref)->toBe($transaction->merchant_ref)
        ->and($transaction->currentAttempt->merchant_session)->toBe($transaction->merchant_session)
        ->and($transaction->currentAttempt->attempt_session)->toBe($transaction->merchant_session)
        ->and($transaction->currentAttempt->attempt_number)->toBe(1);
});

it('does not reserve payment intents when idempotency is disabled', function (): void {
    config()->set('sisp.idempotency.enabled', false);

    $this->post(route('sisp.payment'), transaction_attempts_payment_payload(overrides: [
        'checkout_intent_id' => 'checkout-intent-disabled',
    ]))->assertOk();

    expect(Transaction::query()->count())->toBe(1)
        ->and(TransactionAttempt::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->count())->toBe(0);
});

it('retries identifier collisions when idempotency is disabled', function (): void {
    config()->set('sisp.idempotency.enabled', false);

    Transaction::factory()->create([
        'merchant_ref' => 'MR-DISABLED-COLLISION',
        'merchant_session' => 'MS-DISABLED-COLLISION',
    ]);

    app()->singleton('sisp.test.disabledCollisionReference', fn (): object => new class
    {
        private int $next = 0;

        public function __invoke(): string
        {
            $this->next++;

            return $this->next === 1 ? 'MR-DISABLED-COLLISION' : 'MR-DISABLED-UNIQUE';
        }
    });

    app()->singleton('sisp.test.disabledCollisionSession', fn (): object => new class
    {
        private int $next = 0;

        public function __invoke(): string
        {
            $this->next++;

            return $this->next === 1 ? 'MS-DISABLED-COLLISION' : 'MS-DISABLED-UNIQUE';
        }
    });

    config()->set('sisp.generators.merchantReference', 'sisp.test.disabledCollisionReference');
    config()->set('sisp.generators.merchantSession', 'sisp.test.disabledCollisionSession');

    $this->post(route('sisp.payment'), transaction_attempts_payment_payload(overrides: [
        'checkout_intent_id' => 'checkout-disabled-collision',
    ]))->assertOk();

    $transaction = Transaction::query()
        ->where('merchant_ref', 'MR-DISABLED-UNIQUE')
        ->sole();

    expect($transaction->merchant_session)->toBe('MS-DISABLED-UNIQUE')
        ->and(Transaction::query()->count())->toBe(2)
        ->and(TransactionAttempt::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->count())->toBe(0);
});

it('rejects an existing pending transaction when the checkout intent is posted twice', function (): void {
    $payload = transaction_attempts_payment_payload(overrides: [
        'checkout_intent_id' => 'checkout-intent-duplicate',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $transaction = Transaction::query()->sole();

    $this->postJson(route('sisp.payment'), $payload)
        ->assertConflict()
        ->assertJson([
            'message' => __('sisp::messages.validation.payment_in_progress'),
        ]);

    expect(Transaction::query()->count())->toBe(1)
        ->and(TransactionAttempt::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->sole()->transaction_id)->toBe($transaction->id);
});

it('returns the stored payment request when a failed checkout intent is posted again', function (): void {
    $payload = transaction_attempts_payment_payload(overrides: [
        'idempotency_key' => 'checkout-intent-retry',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $transaction = Transaction::query()->sole();
    $oldRef = $transaction->merchant_ref;
    $oldSession = $transaction->merchant_session;

    $transaction->currentAttempt()->update([
        'status' => TransactionStatus::failed,
        'gateway_transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '13',
        'merchant_response' => 'declined',
        'response_code' => '13',
        'callback_received_at' => now(),
    ]);
    $transaction->update([
        'status' => TransactionStatus::failed,
        'transaction_id' => 'FAILED-GATEWAY-ID',
        'message_type' => '13',
        'merchant_response' => 'declined',
        'response_code' => '13',
        'fingerprint' => 'failed-fingerprint',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk()
        ->assertSee($oldRef);

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect(Transaction::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->sole()->transaction_id)->toBe($transaction->id)
        ->and($attempts)->toHaveCount(1)
        ->and($attempts[0]->merchant_ref)->toBe($oldRef)
        ->and($attempts[0]->merchant_session)->toBe($oldSession)
        ->and($attempts[0]->attempt_session)->toBe($oldSession)
        ->and($attempts[0]->superseded_at)->toBeNull()
        ->and($transaction->merchant_ref)->toBe($oldRef)
        ->and($transaction->merchant_session)->toBe($oldSession)
        ->and($transaction->status)->toBe(TransactionStatus::failed)
        ->and($transaction->transaction_id)->toBe('FAILED-GATEWAY-ID');
});

it('rejects a duplicate checkout intent while the first request is still being reserved', function (): void {
    PaymentIntent::query()->create([
        'idempotency_key' => 'checkout-intent-processing',
        'status' => 'processing',
    ]);

    $this->postJson(route('sisp.payment'), transaction_attempts_payment_payload(overrides: [
        'checkout_intent_id' => 'checkout-intent-processing',
    ]))->assertConflict()
        ->assertJson([
            'message' => __('sisp::messages.validation.payment_in_progress'),
        ]);

    expect(Transaction::query()->count())->toBe(0)
        ->and(TransactionAttempt::query()->count())->toBe(0);
});

it('fails explicitly when custom identifier generators keep colliding', function (): void {
    config()->set('sisp.generators.merchantReference', ConstantMerchantReferenceGenerator::class);
    config()->set('sisp.generators.merchantSession', ConstantMerchantSessionGenerator::class);

    $this->post(route('sisp.payment'), transaction_attempts_payment_payload())
        ->assertOk();

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('sisp.payment'), transaction_attempts_payment_payload()))
        ->toThrow(UnableToGenerateUniquePaymentIdentifiersException::class);

    expect(Transaction::query()->count())->toBe(1)
        ->and(TransactionAttempt::query()->count())->toBe(1);
});

/**
 * @return array<string, mixed>
 */
function transaction_attempts_payment_payload(float $amount = 100.0, array $overrides = []): array
{
    return array_replace_recursive([
        'amount' => $amount,
        'items' => [[
            'product_name' => 'Ticket',
            'quantity' => 1,
            'unit_price' => $amount,
            'total_price' => $amount,
        ]],
        'customer_name' => 'Attempt Customer',
        'customer_email' => 'attempt@example.test',
    ], $overrides);
}
