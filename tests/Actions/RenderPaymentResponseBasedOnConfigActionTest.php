<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseBasedOnConfigAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('uses Inertia when configured for response rendering', function (): void {
    config()->set('sisp.use_inertia.enabled', true);

    $action = resolve(RenderPaymentResponseBasedOnConfigAction::class);
    $transaction = Transaction::factory()->create([
        'amount' => 100.0,
        'currency' => '132',
        'status' => TransactionStatus::pending,
        'merchant_ref' => 'R1',
        'merchant_session' => 'S1',
    ]);
    $result = $action->handle($transaction, []);

    expect($result)->toBeInstanceOf(Inertia\Response::class);
});

it('falls back to Blade when Inertia disabled for response rendering', function (): void {
    config()->set('sisp.use_inertia.enabled', false);

    $action = resolve(RenderPaymentResponseBasedOnConfigAction::class);
    $transaction = Transaction::factory()->create([
        'amount' => 20.0,
        'currency' => '132',
        'status' => TransactionStatus::pending,
        'merchant_ref' => 'R2',
        'merchant_session' => 'S2',
    ]);
    $result = $action->handle($transaction, ['foo' => 'bar']);

    expect($result)->toBeInstanceOf(Illuminate\Contracts\View\View::class);
});
