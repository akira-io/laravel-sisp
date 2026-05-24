<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\InertiaAvailability;
use Illuminate\Contracts\View\View;

it('renders blade view with structured error when message type is error', function (): void {
    $t = Transaction::factory()->create([
        'message_type' => ErrorMessageType::invalidAmount->value,
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($t, ['foo' => 'bar']);
    $error = $view->getData()['error'];

    expect($view->name())->toBe('sisp::payment-response')
        ->and($view->render())->toContain('<!DOCTYPE html>')
        ->and($error)->toMatchArray([
            'code' => ErrorMessageType::invalidAmount->value,
            'label' => ErrorMessageType::invalidAmount->label(),
            'category' => 'validation',
            'action' => 'check-payment-details',
        ]);
});

it('renderInertia returns an inertia response when Inertia is available', function (): void {
    $t = Transaction::factory()->create([
        'message_type' => null,
    ]);

    $result = resolve(RenderPaymentResponseAction::class)->renderInertia($t, ['a' => 1]);
    expect($result)->toBeInstanceOf(Inertia\Response::class);
});

it('renderInertia falls back to blade when Inertia is absent', function (): void {
    app()->instance(InertiaAvailability::class, new InertiaAvailability(false));

    $t = Transaction::factory()->create([
        'message_type' => null,
    ]);

    $result = resolve(RenderPaymentResponseAction::class)->renderInertia($t, ['a' => 1]);

    expect($result)->toBeInstanceOf(View::class)
        ->and($result->name())->toBe('sisp::payment-response');
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

it('provides a signed retry URL when retry is allowed', function (): void {
    $t = Transaction::factory()->failed()->create();

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($t, []);
    $retryUrl = $view->getData()['retryUrl'];

    expect($view->getData()['allowRetry'])->toBeTrue()
        ->and($retryUrl)->toBeString()
        ->and($retryUrl)->toContain('/sisp/retry-payment')
        ->and($retryUrl)->toContain('signature=')
        ->and($retryUrl)->toContain('transaction='.$t->id);
});
