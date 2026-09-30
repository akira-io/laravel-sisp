<?php

declare(strict_types=1);

use Akira\Sisp\Models\Blacklist;
use Akira\Sisp\Models\Transaction;

function paymentPayload(): array
{
    return [
        'amount' => 100.0,
        'items' => [[
            'product_name' => 'Test',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'John',
        'customer_email' => 'john@example.test',
    ];
}

beforeEach(function (): void {
    config()->set('sisp.rate_limiting.enabled', true);
    config()->set('sisp.rate_limiting.per_ip.enabled', true);
    config()->set('sisp.rate_limiting.per_ip.limit', 1);
    config()->set('sisp.rate_limiting.per_ip.window_seconds', 600);
});

it('answers a rate-limited JSON payment request with 429 and Retry-After', function (): void {
    $this->postJson(route('sisp.payment'), paymentPayload())->assertOk();

    $response = $this->postJson(route('sisp.payment'), paymentPayload());

    $response->assertStatus(429)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Rate limit exceeded'));

    expect($response->headers->get('Retry-After'))->toBe('600')
        ->and(Transaction::query()->count())->toBe(1);
});

it('answers a rate-limited browser payment request with 429 instead of 500', function (): void {
    $this->post(route('sisp.payment'), paymentPayload())->assertOk();

    $this->post(route('sisp.payment'), paymentPayload())
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

it('answers a blacklisted JSON payment request with 403', function (): void {
    Blacklist::query()->create(['type' => 'ip', 'value' => '127.0.0.1', 'reason' => 'fraud', 'severity' => 'high']);

    $this->postJson(route('sisp.payment'), paymentPayload())
        ->assertForbidden()
        ->assertJsonPath('message', 'This ip is blacklisted: fraud');

    expect(Transaction::query()->count())->toBe(0);
});

it('answers a blacklisted browser payment request with 403 instead of 500', function (): void {
    Blacklist::query()->create(['type' => 'ip', 'value' => '127.0.0.1', 'reason' => 'fraud', 'severity' => 'high']);

    $this->post(route('sisp.payment'), paymentPayload())->assertForbidden();
});
