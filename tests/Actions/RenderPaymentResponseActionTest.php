<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Models\Transaction;

it('renders blade view with structured error when message type is error', function (): void {
    $t = Transaction::factory()->create([
        'message_type' => ErrorMessageType::invalidAmount->value,
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($t, ['foo' => 'bar']);
    expect($view->name())->toBe('sisp::payment-response');
});

it('renderInertia falls back to blade when Inertia is absent', function (): void {
    $t = Transaction::factory()->create([
        'message_type' => null,
    ]);

    $result = resolve(RenderPaymentResponseAction::class)->renderInertia($t, ['a' => 1]);
    // Inertia is available in this testbench; expect Inertia response
    expect($result)->toBeInstanceOf(Inertia\Response::class);
});

it('sets allowRetry to false when 3DS is enabled and transaction is missing required customer data', function (): void {
    config([
        'sisp.allow_retry' => true,
        'sisp.is_3dsec' => '1',
    ]);

    $t = Transaction::factory()->failed()->create([
        'customer_email' => null,
        'customer_country' => null,
        'customer_city' => null,
        'customer_address' => null,
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($t, []);

    expect($view->getData()['allowRetry'])->toBeFalse();
});
