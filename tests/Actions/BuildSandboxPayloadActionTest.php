<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
});

it('builds failed sandbox payload', function (): void {
    $data = PaymentRequestData::from([
        'amount' => 10.0,
        'merchantRef' => 'ref',
        'merchantSession' => 'sess',
        'currency' => '132',
    ]);

    $payload = resolve(BuildSandboxPayloadAction::class)->handle($data, 'failed');
    expect($payload->messageType)->toBe('6') // issuerError
        ->and($payload->additionalErrorMessage)->not->toBe('');
});

it('builds sandbox payload for unknown status as P', function (): void {
    $data = PaymentRequestData::from([
        'amount' => 5.0,
        'merchantRef' => 'ref',
        'merchantSession' => 'sess',
        'currency' => '132',
    ]);

    $payload = resolve(BuildSandboxPayloadAction::class)->handle($data, 'other');
    expect($payload->messageType)->toBe('P');
});

it('refuses to generate sandbox payloads when sandbox mode is disabled', function (): void {
    config()->set('sisp.sandbox', false);

    $data = PaymentRequestData::from([
        'amount' => 5.0,
        'merchantRef' => 'ref',
        'merchantSession' => 'sess',
        'currency' => '132',
    ]);

    expect(fn () => resolve(BuildSandboxPayloadAction::class)->handle($data))
        ->toThrow(LogicException::class, 'Sandbox payloads can only be generated when SISP sandbox mode is enabled.');
});
