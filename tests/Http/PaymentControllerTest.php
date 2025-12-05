<?php

declare(strict_types=1);

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
});

it('blocks duplicate transactions via middleware', function (): void {
    // Existing processed transaction
    Akira\Sisp\Models\Transaction::factory()->create([
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
