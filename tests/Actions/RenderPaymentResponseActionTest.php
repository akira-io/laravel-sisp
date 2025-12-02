<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\View\View;

beforeEach(function (): void {
    $this->action = resolve(RenderPaymentResponseAction::class);
    $this->config = resolve(LoadConfig::class);
});

it('renders blade view with transaction data', function (): void {
    $transaction = new Transaction([
        'id' => 1,
        'status' => 'completed',
        'merchant_ref' => 'REF123',
        'merchant_session' => 'SESS123',
        'amount' => 10000.0,
        'currency' => 'CVE',
        'formatted_amount' => '10,000.00',
    ]);

    $view = $this->action->renderBlade($transaction, []);

    expect($view)->toBeInstanceOf(View::class)
        ->and($view->getName())->toBe('sisp::payment-response');
});

it('passes allow retry to blade view', function (): void {
    config(['sisp.allow_retry' => true]);

    $transaction = new Transaction([
        'id' => 1,
        'status' => 'failed',
        'merchant_ref' => 'REF123',
        'merchant_session' => 'SESS123',
        'amount' => 10000.0,
        'currency' => 'CVE',
        'formatted_amount' => '10,000.00',
    ]);

    $view = $this->action->renderBlade($transaction, []);

    expect($view->getData()['allowRetry'])->toBeTrue();
});

it('respects allow retry config in blade', function (): void {
    config(['sisp.allow_retry' => false]);

    $transaction = new Transaction([
        'id' => 1,
        'status' => 'failed',
        'merchant_ref' => 'REF123',
        'merchant_session' => 'SESS123',
        'amount' => 10000.0,
        'currency' => 'CVE',
        'formatted_amount' => '10,000.00',
    ]);

    $view = $this->action->renderBlade($transaction, []);

    expect($view->getData()['allowRetry'])->toBeFalse();
});

it('includes error response in blade view when transaction has error', function (): void {
    $transaction = new Transaction([
        'id' => 1,
        'status' => 'failed',
        'merchant_ref' => 'REF123',
        'merchant_session' => 'SESS123',
        'amount' => 10000.0,
        'currency' => 'CVE',
        'formatted_amount' => '10,000.00',
        'message_type' => '51',
    ]);

    $view = $this->action->renderBlade($transaction, []);

    expect($view->getData()['error'])->toBeArray()
        ->toHaveKeys(['code', 'label', 'category', 'categoryLabel', 'action', 'actionLabel']);
});

it('returns null error when transaction has no message type', function (): void {
    $transaction = new Transaction([
        'id' => 1,
        'status' => 'failed',
        'merchant_ref' => 'REF123',
        'merchant_session' => 'SESS123',
        'amount' => 10000.0,
        'currency' => 'CVE',
        'formatted_amount' => '10,000.00',
        'message_type' => null,
    ]);

    $view = $this->action->renderBlade($transaction, []);

    expect($view->getData()['error'])->toBeNull();
});
