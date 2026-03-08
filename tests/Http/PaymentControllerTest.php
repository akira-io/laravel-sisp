<?php

declare(strict_types=1);

use Illuminate\Routing\Router;

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

    $token = 'csrf-token-payment-create';
    $response = $this
        ->withSession(['_token' => $token])
        ->post(route('sisp.payment'), [...$payload, '_token' => $token]);

    $response->assertOk();
    $response->assertSee('sisp-payment-form');
    $response->assertSee(trans('sisp::payment.manual_redirect_button'));
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

    $token = 'csrf-token-payment-duplicate';
    $response = $this
        ->withSession(['_token' => $token])
        ->post(route('sisp.payment'), [...$payload, '_token' => $token]);

    $response->assertRedirect('/');
});

it('applies web middleware to payment route for csrf protection', function (): void {
    $route = resolve(Router::class)->getRoutes()->getByName('sisp.payment');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('web');
});
