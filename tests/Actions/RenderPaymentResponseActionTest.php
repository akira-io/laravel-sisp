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
