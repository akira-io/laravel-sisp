<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\ValueObjects\SispCredentials;

it('creates credentials from config', function (): void {
    config([
        'sisp.posID' => 'TEST_POS',
        'sisp.posAutCode' => 'SECRET',
        'sisp.currency' => '132',
        'sisp.merchantId' => 'MERCHANT_123',
        'sisp.url' => 'https://gateway.example.com',
        'sisp.language_messages' => 'EN',
        'sisp.fingerprint_version' => '1',
        'sisp.is_3dsec' => '0',
        'sisp.sandbox' => false,
        'sisp.url_merchant_response' => 'https://merchant.example.com/callback',
    ]);

    $config = resolve(LoadConfig::class);
    $credentials = SispCredentials::fromConfig($config);

    expect($credentials->posId)->toBe('TEST_POS')
        ->and($credentials->posAutCode)->toBe('SECRET')
        ->and($credentials->currency)->toBe('132')
        ->and($credentials->merchantId)->toBe('MERCHANT_123')
        ->and($credentials->url)->toBe('https://gateway.example.com')
        ->and($credentials->languageMessages)->toBe('EN')
        ->and($credentials->fingerprintVersion)->toBe('1')
        ->and($credentials->is3DSec)->toBe('0')
        ->and($credentials->sandbox)->toBeFalse()
        ->and($credentials->urlMerchantResponse)->toBe('https://merchant.example.com/callback');
});

it('creates credentials from array with snake_case keys', function (): void {
    $credentials = SispCredentials::from([
        'pos_id' => 'TEST_POS',
        'pos_aut_code' => 'SECRET',
        'currency' => '132',
        'merchant_id' => 'MERCHANT_123',
        'url' => 'https://gateway.example.com',
        'language_messages' => 'EN',
        'fingerprint_version' => '1',
        'is_3d_sec' => '1',
        'sandbox' => true,
        'url_merchant_response' => 'https://merchant.example.com/callback',
    ]);

    expect($credentials->posId)->toBe('TEST_POS')
        ->and($credentials->posAutCode)->toBe('SECRET')
        ->and($credentials->currency)->toBe('132')
        ->and($credentials->merchantId)->toBe('MERCHANT_123')
        ->and($credentials->url)->toBe('https://gateway.example.com')
        ->and($credentials->languageMessages)->toBe('EN')
        ->and($credentials->fingerprintVersion)->toBe('1')
        ->and($credentials->is3DSec)->toBe('1')
        ->and($credentials->sandbox)->toBeTrue()
        ->and($credentials->urlMerchantResponse)->toBe('https://merchant.example.com/callback');
});

it('creates credentials from array with camelCase keys', function (): void {
    $credentials = SispCredentials::from([
        'posId' => 'TEST_POS',
        'posAutCode' => 'SECRET',
        'currency' => '132',
        'merchantId' => 'MERCHANT_123',
        'url' => 'https://gateway.example.com',
        'languageMessages' => 'PT',
        'fingerprintVersion' => '2',
        'is3DSec' => '1',
        'sandbox' => false,
        'urlMerchantResponse' => null,
    ]);

    expect($credentials->posId)->toBe('TEST_POS')
        ->and($credentials->posAutCode)->toBe('SECRET')
        ->and($credentials->currency)->toBe('132')
        ->and($credentials->merchantId)->toBe('MERCHANT_123')
        ->and($credentials->url)->toBe('https://gateway.example.com')
        ->and($credentials->languageMessages)->toBe('PT')
        ->and($credentials->fingerprintVersion)->toBe('2')
        ->and($credentials->is3DSec)->toBe('1')
        ->and($credentials->sandbox)->toBeFalse()
        ->and($credentials->urlMerchantResponse)->toBeNull();
});

it('applies default values when creating from array with minimal data', function (): void {
    $credentials = SispCredentials::from([
        'pos_id' => 'TEST_POS',
        'pos_aut_code' => 'SECRET',
        'url' => 'https://gateway.example.com',
    ]);

    expect($credentials->posId)->toBe('TEST_POS')
        ->and($credentials->posAutCode)->toBe('SECRET')
        ->and($credentials->currency)->toBe('132')
        ->and($credentials->merchantId)->toBe('')
        ->and($credentials->url)->toBe('https://gateway.example.com')
        ->and($credentials->languageMessages)->toBe('EN')
        ->and($credentials->fingerprintVersion)->toBe('1')
        ->and($credentials->is3DSec)->toBe('0')
        ->and($credentials->sandbox)->toBeFalse()
        ->and($credentials->urlMerchantResponse)->toBeNull();
});

it('is readonly and immutable', function (): void {
    $credentials = SispCredentials::from([
        'pos_id' => 'TEST_POS',
        'pos_aut_code' => 'SECRET',
        'currency' => '132',
        'merchant_id' => 'MERCHANT_123',
        'url' => 'https://gateway.example.com',
    ]);

    $reflection = new ReflectionClass($credentials);

    expect($reflection->isReadOnly())->toBeTrue()
        ->and($credentials->posId)->toBe('TEST_POS')
        ->and($credentials->posAutCode)->toBe('SECRET');
});
