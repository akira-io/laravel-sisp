<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\GenerateFingerprintAction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('builds payment request payload using defaults and generated fingerprint', function (): void {

    $action = resolve(BuildRequestPayloadAction::class);

    // Provide all fields to ensure determinism
    $data = PaymentRequestData::from([
        'amount' => 123.45,
        'merchantRef' => 'MR-123',
        'merchantSession' => 'MS-456',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
        'token' => 'TOK',
        'entityCode' => 'ENT',
        'referenceNumber' => 'REF',
        'locale' => 'pt_PT',
    ]);

    $request = $action->handle($data);
    $arr = $request->toArray();

    // Compute expected fingerprint using actual generator
    $expectedFingerprint = resolve(GenerateFingerprintAction::class)->handle([
        'timeStamp' => '2024-01-01 00:00:00',
        'amount' => 123.45,
        'merchantRef' => 'MR-123',
        'merchantSession' => 'MS-456',
        'posID' => config('sisp.posID'),
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    expect($arr)
        ->toHaveKeys([
            'posID', 'merchantRef', 'merchantSession', 'amount', 'currency',
            'is3DSec', 'urlMerchantResponse', 'languageMessages', 'timeStamp',
            'fingerprintversion', 'transactionCode', 'fingerprint', 'token',
            'entityCode', 'referenceNumber', 'locale',
        ])
        ->and($arr['posID'])->toBe(config('sisp.posID'))
        ->and($arr['currency'])->toBe('132')
        ->and($arr['is3DSec'])->toBe(config('sisp.is_3dsec'))
        ->and($arr['urlMerchantResponse'])->toBe(config('sisp.url_merchant_response'))
        ->and($arr['languageMessages'])->toBe(config('sisp.language_messages'))
        ->and($arr['fingerprintversion'])->toBe(config('sisp.fingerprint_version'))
        ->and($arr['transactionCode'])->toBe('1')
        ->and($arr['amount'])->toBe(123.45)
        ->and($arr['merchantRef'])->toBe('MR-123')
        ->and($arr['merchantSession'])->toBe('MS-456')
        ->and($arr['token'])->toBe('TOK')
        ->and($arr['entityCode'])->toBe('ENT')
        ->and($arr['referenceNumber'])->toBe('REF')
        ->and($arr['locale'])->toBe('pt_PT')
        ->and($arr['fingerprint'])->toBe($expectedFingerprint);
});
