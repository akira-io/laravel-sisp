<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\SispCredentials;

it('maintains backward compatibility with env credentials', function (): void {
    config([
        'sisp.posID' => 'DEFAULT_POS',
        'sisp.posAutCode' => 'DEFAULT_SECRET',
        'sisp.currency' => '132',
        'sisp.merchantId' => 'DEFAULT_MERCHANT',
        'sisp.url' => 'https://default.gateway.com',
        'sisp.language_messages' => 'EN',
        'sisp.fingerprint_version' => '1',
        'sisp.is_3dsec' => '0',
        'sisp.sandbox' => false,
    ]);

    $request = Sisp::buildRequestPayload(
        PaymentRequestData::from(['amount' => 50.00])
    );

    expect($request->toArray()['posID'])->toBe('DEFAULT_POS');
});

it('uses scoped credentials without mutating global state', function (): void {
    config([
        'sisp.posID' => 'DEFAULT_POS',
        'sisp.posAutCode' => 'DEFAULT_SECRET',
        'sisp.currency' => '132',
        'sisp.merchantId' => 'DEFAULT_MERCHANT',
        'sisp.url' => 'https://default.gateway.com',
    ]);

    $customCreds = SispCredentials::from([
        'pos_id' => 'CUSTOM_POS',
        'pos_aut_code' => 'CUSTOM_SECRET',
        'currency' => '132',
        'merchant_id' => 'CUSTOM_MERCHANT',
        'url' => 'https://custom.gateway.com',
    ]);

    $scoped = Sisp::forCredentials($customCreds)
        ->buildRequestPayload(PaymentRequestData::from(['amount' => 100.00]));

    $default = Sisp::buildRequestPayload(
        PaymentRequestData::from(['amount' => 100.00])
    );

    expect($scoped->toArray()['posID'])->toBe('CUSTOM_POS')
        ->and($default->toArray()['posID'])->toBe('DEFAULT_POS');
});

it('handles multiple merchants in single request without state pollution', function (): void {
    $merchantA = SispCredentials::from([
        'pos_id' => 'MERCHANT_A_POS',
        'pos_aut_code' => 'SECRET_A',
        'currency' => '132',
        'merchant_id' => 'MERCHANT_A',
        'url' => 'https://gateway.example.com',
    ]);

    $merchantB = SispCredentials::from([
        'pos_id' => 'MERCHANT_B_POS',
        'pos_aut_code' => 'SECRET_B',
        'currency' => '132',
        'merchant_id' => 'MERCHANT_B',
        'url' => 'https://gateway.example.com',
    ]);

    $requestA = Sisp::forCredentials($merchantA)
        ->buildRequestPayload(PaymentRequestData::from(['amount' => 100.00]));

    $requestB = Sisp::forCredentials($merchantB)
        ->buildRequestPayload(PaymentRequestData::from(['amount' => 200.00]));

    expect($requestA->toArray()['posID'])->toBe('MERCHANT_A_POS')
        ->and($requestB->toArray()['posID'])->toBe('MERCHANT_B_POS')
        ->and($requestA->toArray()['amount'])->toBe(100.00)
        ->and($requestB->toArray()['amount'])->toBe(200.00);
});

it('allows credential getter methods on scoped instance', function (): void {
    $credentials = SispCredentials::from([
        'pos_id' => 'SCOPED_POS',
        'pos_aut_code' => 'SCOPED_SECRET',
        'currency' => '978',
        'merchant_id' => 'SCOPED_MERCHANT',
        'url' => 'https://scoped.gateway.com',
        'language_messages' => 'PT',
        'fingerprint_version' => '2',
        'is_3d_sec' => '1',
        'sandbox' => true,
        'url_merchant_response' => 'https://scoped.callback.com',
    ]);

    $scoped = Sisp::forCredentials($credentials);

    expect($scoped->getPosId())->toBe('SCOPED_POS')
        ->and($scoped->getPosAutCode())->toBe('SCOPED_SECRET')
        ->and($scoped->getCurrency())->toBe('978')
        ->and($scoped->getUri())->toBe('https://scoped.gateway.com')
        ->and($scoped->getLanguageMessages())->toBe('PT')
        ->and($scoped->getFingerprintVersion())->toBe('2')
        ->and($scoped->getIs3Dsec())->toBe('1')
        ->and($scoped->getUrlMerchantResponse())->toBe('https://scoped.callback.com');
});

it('generates sandbox payload with scoped credentials', function (): void {
    $credentials = SispCredentials::from([
        'pos_id' => 'SANDBOX_POS',
        'pos_aut_code' => 'SANDBOX_SECRET',
        'currency' => '132',
        'merchant_id' => 'SANDBOX_MERCHANT',
        'url' => 'https://sandbox.gateway.com',
        'sandbox' => true,
    ]);

    $payload = Sisp::forCredentials($credentials)
        ->generateSandboxPayload(
            PaymentRequestData::from(['amount' => 75.00]),
            'success'
        );

    expect($payload->posID)->toBe('SANDBOX_POS');
});

it('validates callback with scoped credentials', function (): void {
    config([
        'sisp.posID' => 'DEFAULT_POS',
        'sisp.posAutCode' => 'DEFAULT_SECRET',
        'sisp.currency' => '132',
        'sisp.merchantId' => 'DEFAULT_MERCHANT',
        'sisp.url' => 'https://default.gateway.com',
    ]);

    $credentials = SispCredentials::from([
        'pos_id' => 'CALLBACK_POS',
        'pos_aut_code' => 'CALLBACK_SECRET',
        'currency' => '132',
        'merchant_id' => 'CALLBACK_MERCHANT',
        'url' => 'https://callback.gateway.com',
    ]);

    $scoped = Sisp::forCredentials($credentials);

    expect($scoped->getPosId())->toBe('CALLBACK_POS');
});
