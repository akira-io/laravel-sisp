<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

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
