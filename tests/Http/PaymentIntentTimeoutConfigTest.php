<?php

declare(strict_types=1);

use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.rate_limiting.enabled', false);
});

function payment_intent_timeout_payload(string $key): array
{
    return [
        'amount' => 100.0,
        'items' => [[
            'product_name' => 'Ticket',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'Intent Customer',
        'customer_email' => 'intent@example.test',
        'checkout_intent_id' => $key,
    ];
}

it('reclaims a stuck intent at exactly the default timeout when the key is absent from the config', function (): void {
    $this->freezeTime();
    config()->set('sisp.idempotency', ['enabled' => true, 'request_keys' => ['checkout_intent_id']]);
    PaymentIntent::query()->create(['idempotency_key' => 'default-timeout-intent', 'status' => 'processing']);
    PaymentIntent::query()->where('idempotency_key', 'default-timeout-intent')->update(['updated_at' => now()->subSeconds(600)]);

    $this->post(route('sisp.payment'), payment_intent_timeout_payload('default-timeout-intent'))->assertOk();

    expect(PaymentIntent::query()->sole()->status)->toBe('submitted');
});

it('treats a negative timeout as never reclaiming', function (): void {
    config()->set('sisp.idempotency.processing_timeout_seconds', -5);
    PaymentIntent::query()->create(['idempotency_key' => 'negative-timeout-intent', 'status' => 'processing']);
    PaymentIntent::query()->where('idempotency_key', 'negative-timeout-intent')->update(['updated_at' => now()->subDay()]);

    $this->post(route('sisp.payment'), payment_intent_timeout_payload('negative-timeout-intent'));

    expect(PaymentIntent::query()->sole()->status)->toBe('processing')
        ->and(Transaction::query()->count())->toBe(0);
});
