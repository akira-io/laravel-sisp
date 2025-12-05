<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\LoadConfig;

it('returns blade views and allows overriding', function (): void {
    $cfg = resolve(LoadConfig::class);

    expect($cfg->getPaymentFormView())->toBe('sisp::payment-form')
        ->and($cfg->getPaymentResponseView())->toBe('sisp::payment-response');

    config()->set('sisp.use_blade.payment_form', 'pkg::pf');
    config()->set('sisp.use_blade.payment_response', 'pkg::pr');

    expect($cfg->getPaymentFormView())->toBe('pkg::pf')
        ->and($cfg->getPaymentResponseView())->toBe('pkg::pr');
});

it('returns inertia components and shouldUseInertia respects config', function (): void {
    $cfg = resolve(LoadConfig::class);

    // Default components
    expect($cfg->getPaymentFormComponent())->toBe('sisp/payment-form')
        ->and($cfg->getPaymentResponseComponent())->toBe('sisp/payment-response');

    // When enabled and class exists, shouldUseInertia true
    config()->set('sisp.use_inertia.enabled', true);
    expect($cfg->shouldUseInertia())->toBeTrue();

    // When disabled, shouldUseInertia false
    config()->set('sisp.use_inertia.enabled', false);
    expect($cfg->shouldUseInertia())->toBeFalse();
});
