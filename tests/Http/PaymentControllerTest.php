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
