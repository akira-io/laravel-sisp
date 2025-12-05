<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildSandboxPayloadAction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('builds sandbox callback payload with success status', function (): void {
    $action = resolve(BuildSandboxPayloadAction::class);
    $data = PaymentRequestData::from([
        'amount' => 50.0,
        'merchantRef' => 'MR-1',
        'merchantSession' => 'MS-1',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => config('sisp.currency'),
        'transactionCode' => config('sisp.transaction_code'),
    ]);

    $payload = $action->handle($data, 'success');
    $arr = $payload->toArray();

    expect($arr)
        ->toHaveKey('resultFingerPrint')
        ->and($arr['resultFingerPrint'])->not->toBe('')
        ->and($arr['messageType'])->toBe('8');
});

it('builds sandbox callback payload with failed status and error message', function (): void {
    $action = resolve(BuildSandboxPayloadAction::class);
    $data = PaymentRequestData::from([
        'amount' => 75.0,
        'merchantRef' => 'MR-2',
        'merchantSession' => 'MS-2',
        'timeStamp' => '2024-01-02 00:00:00',
        'currency' => config('sisp.currency'),
        'transactionCode' => config('sisp.transaction_code'),
    ]);

    $payload = $action->handle($data, 'failed');
    $arr = $payload->toArray();

    expect($arr)
        ->toHaveKey('merchantRespAdditionalErrorMessage')
        ->and($arr['merchantRespAdditionalErrorMessage'])->not->toBe('')
        ->and($arr['resultFingerPrint'])->not->toBe('');
});
