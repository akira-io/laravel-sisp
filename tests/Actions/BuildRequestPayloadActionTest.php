<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildRequestPayloadAction;
use Akira\Sisp\Actions\GenerateFingerprintAction;
use Akira\Sisp\Exceptions\MissingThreeDSecureDataException;
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

it('does not include purchaseRequest when 3DS is disabled', function (): void {
    config(['sisp.is_3dsec' => '0']);

    $action = resolve(BuildRequestPayloadAction::class);

    $data = PaymentRequestData::from([
        'amount' => 100.0,
    ]);

    $request = $action->handle($data);
    $arr = $request->toArray();

    expect($arr)->not->toHaveKey('purchaseRequest');
});

it('generates purchaseRequest when 3DS enabled and customer data complete', function (): void {
    config(['sisp.is_3dsec' => '1']);

    $action = resolve(BuildRequestPayloadAction::class);

    $data = PaymentRequestData::from([
        'amount' => 100.0,
        'customer_email' => 'test@example.com',
        'customer_country' => 'cv',
        'customer_city' => 'Praia',
        'customer_address' => 'Rua Principal',
        'customer_postal_code' => '7600',
        'customer_phone' => '9123456',
    ]);

    $request = $action->handle($data);
    $arr = $request->toArray();

    expect($arr)->toHaveKey('purchaseRequest')
        ->and($arr['purchaseRequest'])->not->toBeEmpty()
        ->and(base64_decode((string) $arr['purchaseRequest'], true))->not->toBeFalse();

    $decoded = json_decode(base64_decode((string) $arr['purchaseRequest']), true);

    expect($decoded['email'])->toBe('test@example.com')
        ->and($decoded['billAddrCountry'])->toBe('132')
        ->and($decoded['billAddrCity'])->toBe('Praia');
});

it('throws exception when 3DS enabled but customer data incomplete', function (): void {
    config(['sisp.is_3dsec' => '1']);

    $action = resolve(BuildRequestPayloadAction::class);

    $data = PaymentRequestData::from([
        'amount' => 100.0,
        'customer_email' => 'test@example.com',
        // Missing: country, city, address, postal_code
    ]);

    expect(fn () => $action->handle($data))
        ->toThrow(MissingThreeDSecureDataException::class, '3D Secure is enabled but required customer data is missing');
});

it('exception message lists missing fields', function (): void {
    config(['sisp.is_3dsec' => '1']);

    $action = resolve(BuildRequestPayloadAction::class);

    $data = PaymentRequestData::from([
        'amount' => 100.0,
        'customer_email' => 'test@example.com',
        'customer_city' => 'Praia',
        // Missing: country, address, postal_code
    ]);

    try {
        $action->handle($data);
        expect(false)->toBeTrue('Exception should have been thrown');
    } catch (MissingThreeDSecureDataException $e) {
        expect($e->getMessage())->toContain('customer_country')
            ->and($e->getMessage())->toContain('customer_address')
            ->and($e->getMessage())->toContain('customer_postal_code')
            ->and($e->getMessage())->not->toContain('customer_email')
            ->and($e->getMessage())->not->toContain('customer_city');
    }
});
