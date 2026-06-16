<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\MerchantReferenceGeneratorAction;
use Akira\Sisp\Actions\Generators\MerchantSessionGeneratorAction;
use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;

final class TransientFailureMerchantReferenceGenerator
{
    public function __invoke(): string
    {
        return 'MR-TRANSIENT-FAILURE';
    }
}

final class TransientFailureMerchantSessionGenerator
{
    public function __invoke(): string
    {
        return 'MS-TRANSIENT-FAILURE';
    }
}

beforeEach(function (): void {
    config([
        'sisp.sandbox' => true,
        'sisp.rate_limiting.enabled' => false,
        'sisp.identifier_generation.collision_retry_sleep_microseconds' => 0,
    ]);
});

it('reclaims a failed checkout intent reservation when the same key is posted again', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'MR-TRANSIENT-FAILURE',
        'merchant_session' => 'MS-BLOCKING-TRANSACTION',
    ]);

    config([
        'sisp.generators.merchantReference' => TransientFailureMerchantReferenceGenerator::class,
        'sisp.generators.merchantSession' => TransientFailureMerchantSessionGenerator::class,
        'sisp.identifier_generation.max_attempts' => 1,
    ]);

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

    config([
        'sisp.generators.merchantReference' => MerchantReferenceGeneratorAction::class,
        'sisp.generators.merchantSession' => MerchantSessionGeneratorAction::class,
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $intent = PaymentIntent::query()->sole();

    expect($intent->status)->toBe('submitted')
        ->and($intent->transaction_id)->not->toBeNull()
        ->and($intent->failure_reason)->toBeNull()
        ->and(Transaction::query()->count())->toBe(2)
        ->and(TransactionAttempt::query()->count())->toBe(1);
});

function payment_intent_reservation_payload(array $overrides = []): array
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
