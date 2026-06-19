<?php

declare(strict_types=1);

use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;

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

final class ReferenceCollisionMerchantSessionGenerator
{
    private static int $count = 0;

    public function __invoke(): string
    {
        self::$count++;

        return 'MS-REF-COLLISION-'.self::$count;
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
        ->and($attempt->attempt_session)->toBe($transaction->merchant_session)
        ->and($attempt->status->value)->toBe('pending')
        ->and($attempt->payload)->toBeArray();
});

it('rejects an existing pending transaction when the checkout intent is posted twice', function (): void {
    $payload = transaction_attempt_payment_payload([
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

it('reuses the same SISP identifiers when a failed checkout intent is posted again', function (): void {
    $payload = transaction_attempt_payment_payload([
        'idempotency_key' => 'checkout-intent-retry',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $transaction = Transaction::query()->sole();
    $oldRef = $transaction->merchant_ref;
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
        ->assertOk()
        ->assertSee($oldRef);

    $transaction->refresh();
    $attempts = $transaction->attempts()->orderBy('attempt_number')->get();

    expect(Transaction::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->sole()->transaction_id)->toBe($transaction->id)
        ->and($attempts)->toHaveCount(2)
        ->and($attempts[0]->merchant_ref)->toBe($oldRef)
        ->and($attempts[0]->merchant_session)->toBe($oldSession)
        ->and($attempts[0]->attempt_session)->toBe($oldSession)
        ->and($attempts[0]->superseded_at)->not->toBeNull()
        ->and($attempts[1]->merchant_ref)->toBe($oldRef)
        ->and($attempts[1]->merchant_session)->toBe($oldSession)
        ->and($attempts[1]->attempt_session)->not->toBe($oldSession)
        ->and($attempts[1]->payload['merchantSession'])->toBe($oldSession)
        ->and($attempts[1]->superseded_at)->toBeNull()
        ->and($transaction->merchant_ref)->toBe($oldRef)
        ->and($transaction->merchant_session)->toBe($oldSession)
        ->and($transaction->status->value)->toBe('failed')
        ->and($transaction->transaction_id)->toBe('FAILED-GATEWAY-ID');
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

it('returns a conflict when a submitted checkout intent has no reusable payment payload', function (): void {
    $transaction = Transaction::factory()->create([
        'payload' => null,
        'status' => 'pending',
    ]);

    TransactionAttempt::factory()->create([
        'transaction_id' => $transaction->id,
        'merchant_ref' => $transaction->merchant_ref,
        'merchant_session' => $transaction->merchant_session,
        'payload' => null,
    ]);

    PaymentIntent::query()->create([
        'idempotency_key' => 'checkout-intent-null-payload',
        'transaction_id' => $transaction->id,
        'status' => 'submitted',
    ]);

    $this->postJson(route('sisp.payment'), transaction_attempt_payment_payload([
        'checkout_intent_id' => 'checkout-intent-null-payload',
    ]))->assertConflict()
        ->assertJson([
            'message' => __('sisp::messages.validation.payment_in_progress'),
        ]);

    expect(Transaction::query()->count())->toBe(1)
        ->and(TransactionAttempt::query()->count())->toBe(1);
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

it('fails without persisting a duplicate when only the merchant reference keeps colliding', function (): void {
    config([
        'sisp.generators.merchantReference' => ConstantMerchantReferenceGenerator::class,
        'sisp.generators.merchantSession' => ReferenceCollisionMerchantSessionGenerator::class,
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
