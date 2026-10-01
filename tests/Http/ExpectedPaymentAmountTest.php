<?php

declare(strict_types=1);

use Akira\Sisp\Contracts\PaymentAmountResolver;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Request;

function checkoutPayload(float $amount, string $intent = 'order-42'): array
{
    return [
        'amount' => $amount,
        'items' => [[
            'product_name' => 'Ticket',
            'quantity' => 1,
            'unit_price' => $amount,
            'total_price' => $amount,
        ]],
        'customer_name' => 'John',
        'customer_email' => 'john@example.test',
        'checkout_intent_id' => $intent,
    ];
}

function expectAmountForOrder(?float $expected): void
{
    app()->instance(PaymentAmountResolver::class, new readonly class($expected) implements PaymentAmountResolver
    {
        public function __construct(private ?float $expected) {}

        public function expectedAmount(Request $request): ?float
        {
            return $request->input('checkout_intent_id') === 'order-42' ? $this->expected : null;
        }
    });
}

beforeEach(function (): void {
    config()->set('sisp.rate_limiting.enabled', false);
});

it('rejects a submitted amount that differs from the amount the server expects', function (): void {
    expectAmountForOrder(250.0);

    $this->postJson(route('sisp.payment'), checkoutPayload(1.0))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    expect(Transaction::query()->count())->toBe(0);
});

it('redirects a browser back with the amount error instead of rendering the form', function (): void {
    expectAmountForOrder(250.0);

    $this->from('/checkout')
        ->post(route('sisp.payment'), checkoutPayload(1.0))
        ->assertRedirect('/checkout')
        ->assertSessionHasErrors('amount');

    expect(Transaction::query()->count())->toBe(0);
});

it('accepts a submitted amount that matches the expected one to the thousandth', function (): void {
    expectAmountForOrder(250.0);

    $this->post(route('sisp.payment'), checkoutPayload(250.0))->assertOk();

    expect(Transaction::query()->sole()->amount)->toBe(250.0);
});

it('accepts the submitted amount when the resolver has no opinion', function (): void {
    expectAmountForOrder(250.0);

    $this->post(route('sisp.payment'), checkoutPayload(1.0, 'unknown-order'))->assertOk();

    expect(Transaction::query()->sole()->amount)->toBe(1.0);
});

it('accepts the submitted amount by default', function (): void {
    $this->post(route('sisp.payment'), checkoutPayload(1.0))->assertOk();

    expect(Transaction::query()->count())->toBe(1);
});

it('refuses an amount too large to express instead of failing the request', function (): void {

    $this->postJson(route('sisp.payment'), checkoutPayload(1.0e17))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    expect(Transaction::query()->count())->toBe(0);
});

it('lets the resolver refuse an unknown checkout with a validation error', function (): void {
    app()->instance(PaymentAmountResolver::class, new class implements PaymentAmountResolver
    {
        public function expectedAmount(Request $request): ?float
        {
            throw Illuminate\Validation\ValidationException::withMessages(['checkout_intent_id' => 'This checkout is unknown.']);
        }
    });

    $this->postJson(route('sisp.payment'), checkoutPayload(1.0, 'unknown-order'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('checkout_intent_id');

    expect(Transaction::query()->count())->toBe(0);
});
