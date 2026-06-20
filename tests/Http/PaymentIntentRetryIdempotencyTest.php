<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.rate_limiting.enabled', false);
    config()->set('sisp.identifier_generation.collision_retry_sleep_microseconds', 0);
});

it('returns the existing pending retry attempt for duplicate failed checkout intents', function (): void {
    $payload = payment_intent_retry_payload([
        'idempotency_key' => 'checkout-intent-repeat-retry',
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $transaction = Transaction::query()->sole();

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
    ]);

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    $retryAttempt = $transaction->refresh()->currentAttempt;

    $this->post(route('sisp.payment'), $payload)
        ->assertOk();

    expect($transaction->attempts()->count())->toBe(2)
        ->and($transaction->refresh()->currentAttempt->is($retryAttempt))->toBeTrue();
});

function payment_intent_retry_payload(array $overrides = []): array
{
    return array_replace_recursive([
        'amount' => 100.0,
        'items' => [[
            'product_name' => 'Ticket',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'Attempt Customer',
        'customer_email' => 'attempt@example.test',
    ], $overrides);
}
