<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\MerchantReferenceGeneratorAction;
use Akira\Sisp\Actions\Generators\MerchantSessionGeneratorAction;
use Akira\Sisp\Contracts\Generator;
use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;

final readonly class PaymentIntentReservationMerchantReferenceGenerator implements Generator
{
    public function __invoke(): string
    {
        return 'R-TRANSIENT-FAILURE';
    }
}

final readonly class PaymentIntentReservationMerchantSessionGenerator implements Generator
{
    public function __invoke(): string
    {
        return 'S-TRANSIENT-FAILURE';
    }
}

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.rate_limiting.enabled', false);
    config()->set('sisp.identifier_generation.collision_retry_sleep_microseconds', 0);
});

it('reclaims a failed checkout intent reservation when the same key is posted again', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'R-TRANSIENT-FAILURE',
        'merchant_session' => 'S-BLOCKING-TRANSACTION',
    ]);

    config()->set('sisp.generators.merchantReference', PaymentIntentReservationMerchantReferenceGenerator::class);
    config()->set('sisp.generators.merchantSession', PaymentIntentReservationMerchantSessionGenerator::class);
    config()->set('sisp.identifier_generation.max_attempts', 1);

    $payload = payment_intent_reservation_payload([
        'checkout_intent_id' => 'checkout-intent-transient-failure',
    ]);

    $this->withoutExceptionHandling();

    expect(fn () => $this->post(route('sisp.payment'), $payload))
        ->toThrow(UnableToGenerateUniquePaymentIdentifiersException::class);

    expect(PaymentIntent::query()->sole())
        ->status->toBe('failed')
        ->transaction_id->toBeNull()
        ->failure_reason->not->toBeNull();

    $this->withExceptionHandling();

    config()->set('sisp.generators.merchantReference', MerchantReferenceGeneratorAction::class);
    config()->set('sisp.generators.merchantSession', MerchantSessionGeneratorAction::class);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $intent = PaymentIntent::query()->sole();

    expect($intent->status)->toBe('submitted')
        ->and($intent->transaction_id)->not->toBeNull()
        ->and($intent->failure_reason)->toBeNull()
        ->and(Transaction::query()->count())->toBe(2)
        ->and(TransactionAttempt::query()->count())->toBe(1);
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

    $this->postJson(route('sisp.payment'), payment_intent_reservation_payload([
        'checkout_intent_id' => 'checkout-intent-null-payload',
    ]))->assertConflict()
        ->assertJson([
            'message' => __('sisp::messages.validation.payment_in_progress'),
        ]);

    expect(Transaction::query()->count())->toBe(1)
        ->and(TransactionAttempt::query()->count())->toBe(1);
});

function payment_intent_reservation_payload(array $overrides = []): array
{
    return array_replace_recursive([
        'amount' => 100.0,
        'items' => [[
            'product_name' => 'Ticket',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'Intent Customer',
        'customer_email' => 'intent@example.test',
    ], $overrides);
}
