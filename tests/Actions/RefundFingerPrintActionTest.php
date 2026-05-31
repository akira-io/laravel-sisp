<?php

declare(strict_types=1);

use Akira\Sisp\Actions\FingerPrint\RefundFingerPrintAction;

it('generates the known SISP refund fingerprint vector', function (): void {
    $fingerprint = resolve(RefundFingerPrintAction::class)->handle([
        'timeStamp' => '2026-05-14 10:30:00',
        'amount' => 1500,
        'merchantRef' => 'R20260514103000',
        'merchantSession' => 'S20260514103000',
        'posID' => 'TEST_POS_001',
        'currency' => '132',
        'transactionCode' => '8',
        'clearingPeriod' => '5',
        'transactionID' => '123',
    ]);

    expect($fingerprint)->toBe('4Gdsr9+jbhQ97aDAQJ9DJge4giSG9hgEKEJxFaCWl4U/upDJWRCqs3xHkX21rpejYBHS1oQW6OBXaMPW4zN4+w==');
});

it('uses refund identifiers when generating fingerprints', function (): void {
    $action = resolve(RefundFingerPrintAction::class);

    $base = [
        'timeStamp' => '2026-05-14 10:30:00',
        'amount' => 1500,
        'merchantRef' => 'R20260514103000',
        'merchantSession' => 'S20260514103000',
        'posID' => 'TEST_POS_001',
        'currency' => '132',
        'transactionCode' => '8',
        'clearingPeriod' => '5',
        'transactionID' => '123',
    ];

    expect($action->handle($base))->not->toBe($action->handle([...$base, 'transactionID' => '124']));
});
