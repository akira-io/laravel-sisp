<?php

declare(strict_types=1);

use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;

it('creates transaction and renders payment form', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);

    $payload = [
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

    $response = $this->post(route('sisp.payment'), $payload);

    $response->assertOk();
    $response->assertSee('sisp-payment-form');
    $response->assertSee(trans('sisp::payment.manual_redirect_button'));

    $transaction = Transaction::query()->sole();

    expect($transaction->amount)->toBe(100.0)
        ->and($transaction->amount_cents)->toBe(10000)
        ->and($transaction->currency)->toBe('132')
        ->and($transaction->status->value)->toBe('pending')
        ->and($transaction->customer_name)->toBe('John')
        ->and($transaction->customer_email)->toBe('john@example.test')
        ->and($transaction->merchant_ref)->not->toBe('')
        ->and($transaction->merchant_session)->not->toBe('')
        ->and($transaction->items)->toHaveCount(1)
        ->and($transaction->items->first()->product_name)->toBe('Test')
        ->and($transaction->items->first()->quantity)->toBe(1)
        ->and((float) $transaction->items->first()->total_price)->toBe(100.0);
});

it('stores decimal payment amounts with canonical cents', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);

    $this->post(route('sisp.payment'), [
        'amount' => 8.03,
        'items' => [[
            'product_name' => 'Decimal Amount',
            'quantity' => 1,
            'unit_price' => 8.03,
            'total_price' => 8.03,
        ]],
        'customer_name' => 'Decimal Customer',
        'customer_email' => 'decimal@example.test',
    ])->assertOk();

    $transaction = Transaction::query()->sole();

    expect($transaction->amount)->toBe(8.03)
        ->and($transaction->amount_cents)->toBe(803);
});

it('respects disabled idempotency and metadata collection flags', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);
    config()->set('sisp.idempotency.enabled', false);
    config()->set('sisp.security.collect_metadata', false);

    $this->post(route('sisp.payment'), [
        'amount' => 100.0,
        'checkout_intent_id' => 'checkout-disabled-flags',
        'items' => [[
            'product_name' => 'Test',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'John',
        'customer_email' => 'john@example.test',
    ])->assertOk();

    expect(Transaction::query()->count())->toBe(1)
        ->and(PaymentIntent::query()->count())->toBe(0)
        ->and(TransactionAttempt::query()->count())->toBe(0)
        ->and(RequestMetadata::query()->count())->toBe(0);
});

it('retries identifier collisions when idempotency is disabled', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);
    config()->set('sisp.idempotency.enabled', false);
    config()->set('sisp.identifier_generation.max_attempts', 2);
    config()->set('sisp.identifier_generation.collision_retry_sleep_microseconds', 0);

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

    $this->post(route('sisp.payment'), [
        'amount' => 100.0,
        'checkout_intent_id' => 'checkout-disabled-collision',
        'items' => [[
            'product_name' => 'Test',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'John',
        'customer_email' => 'john@example.test',
    ])->assertOk();

    $transaction = Transaction::query()
        ->where('merchant_ref', 'MR-DISABLED-UNIQUE')
        ->sole();

    expect($transaction->merchant_session)->toBe('MS-DISABLED-UNIQUE')
        ->and(Transaction::query()->count())->toBe(2)
        ->and(PaymentIntent::query()->count())->toBe(0)
        ->and(TransactionAttempt::query()->count())->toBe(0);
});

it('blocks duplicate transactions via middleware', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'MR-DUP',
        'merchant_session' => 'MS-DUP',
        'status' => 'completed',
    ]);

    $payload = [
        'amount' => 50.0,
        'merchantRef' => 'MR-DUP',
        'merchantSession' => 'MS-DUP',
        'items' => [[
            'product_name' => 'Test',
            'quantity' => 1,
            'unit_price' => 50.0,
            'total_price' => 50.0,
        ]],
    ];

    $response = $this->post(route('sisp.payment'), $payload);

    $response->assertRedirect('/');
});

it('rejects item totals that do not match quantity multiplied by unit price', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);

    $this->postJson(route('sisp.payment'), [
        'amount' => 100.0,
        'items' => [[
            'product_name' => 'Test',
            'quantity' => 2,
            'unit_price' => 50.0,
            'total_price' => 90.0,
        ]],
    ])->assertJsonValidationErrors(['items.0.total_price', 'amount']);

    expect(Transaction::query()->count())->toBe(0);
});

it('rejects payment amount that does not match item totals', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);

    $this->postJson(route('sisp.payment'), [
        'amount' => 120.0,
        'items' => [[
            'product_name' => 'Test',
            'quantity' => 2,
            'unit_price' => 50.0,
            'total_price' => 100.0,
        ]],
    ])->assertJsonValidationErrors(['amount']);

    expect(Transaction::query()->count())->toBe(0);
});
