<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

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

