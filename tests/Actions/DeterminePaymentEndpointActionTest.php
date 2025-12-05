<?php

declare(strict_types=1);

use Akira\Sisp\Actions\DeterminePaymentEndpointAction;

it('returns sandbox route when sandbox enabled', function (): void {
    config()->set('sisp.sandbox', true);

    $action = resolve(DeterminePaymentEndpointAction::class);
    $endpoint = $action->handle();

    expect($endpoint)->toBe(route('sisp.sandbox'));
});

it('returns configured URI when sandbox disabled', function (): void {
    config()->set('sisp.sandbox', false);
    config()->set('sisp.url', 'https://payments.example.test/endpoint');

    $action = resolve(DeterminePaymentEndpointAction::class);
    $endpoint = $action->handle();

    expect($endpoint)->toBe('https://payments.example.test/endpoint');
});
