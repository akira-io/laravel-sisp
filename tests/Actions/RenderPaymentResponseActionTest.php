<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\InertiaAvailability;
use Illuminate\Contracts\View\View;

it('renders blade view with structured error built from the transaction error fields', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'message_type' => '6',
        'error_code' => '13',
        'error_message' => 'Valor invalido',
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($t, ['foo' => 'bar']);
    $error = $view->getData()['error'];

    expect($view->name())->toBe('sisp::payment-response')
        ->and($view->render())->toContain('<!DOCTYPE html>')
        ->and($error)->toMatchArray([
            'code' => '13',
            'label' => 'Valor invalido',
            'category' => 'unknown',
            'action' => 'contact-support',
        ]);
});

it('shows the message the SISP designated for the customer', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'message_type' => '6',
        'error_code' => '3',
        'error_message' => 'Saldo do cartao insuficiente',
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($transaction, []);

    expect($view->getData()['error']['label'])->toBe('Saldo do cartao insuficiente')
        ->and($view->getData()['error']['code'])->toBe('3');
});

it('falls back to a generic message instead of naming a cause it does not know', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'message_type' => '6',
        'error_message' => null,
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($transaction, []);

    expect($view->getData()['error']['label'])
        ->toBe(__('sisp::messages.errors.labels.unknown'));
});

it('does not claim an issuer error for message type 6', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'failed',
        'message_type' => '6',
        'error_message' => 'Saldo do cartao insuficiente',
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($transaction, []);

    expect($view->getData()['error']['label'])
        ->not->toBe(__('sisp::messages.errors.labels.issuerError'));
});

it('has no structured error for a completed transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'message_type' => '8',
    ]);

    $view = resolve(RenderPaymentResponseAction::class)->renderBlade($transaction, []);

    expect($view->getData()['error'])->toBeNull();
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
