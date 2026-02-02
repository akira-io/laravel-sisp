<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\EnvSispCredentialsResolver;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\ValueObjects\SispCredentials;

it('resolves credentials from config', function (): void {
    config([
        'sisp.posID' => 'ENV_POS_123',
        'sisp.posAutCode' => 'ENV_SECRET',
        'sisp.currency' => '132',
        'sisp.merchantId' => 'ENV_MERCHANT',
        'sisp.url' => 'https://env.gateway.com',
        'sisp.language_messages' => 'PT',
        'sisp.fingerprint_version' => '1',
        'sisp.is_3dsec' => '1',
        'sisp.sandbox' => true,
        'sisp.url_merchant_response' => 'https://env.callback.com',
    ]);

    $config = resolve(LoadConfig::class);
    $resolver = new EnvSispCredentialsResolver($config);
    $credentials = $resolver->resolve();

    expect($credentials)->toBeInstanceOf(SispCredentials::class)
        ->and($credentials->posId)->toBe('ENV_POS_123')
        ->and($credentials->posAutCode)->toBe('ENV_SECRET')
        ->and($credentials->currency)->toBe('132')
        ->and($credentials->merchantId)->toBe('ENV_MERCHANT')
        ->and($credentials->url)->toBe('https://env.gateway.com')
        ->and($credentials->languageMessages)->toBe('PT')
        ->and($credentials->fingerprintVersion)->toBe('1')
        ->and($credentials->is3DSec)->toBe('1')
        ->and($credentials->sandbox)->toBeTrue()
        ->and($credentials->urlMerchantResponse)->toBe('https://env.callback.com');
});

it('applies fallback defaults when config values are missing', function (): void {
    config([
        'sisp.posID' => 'POS_123',
        'sisp.posAutCode' => 'SECRET',
        'sisp.url' => 'https://gateway.com',
    ]);

    $config = resolve(LoadConfig::class);
    $resolver = new EnvSispCredentialsResolver($config);
    $credentials = $resolver->resolve();

    expect($credentials->posId)->toBe('POS_123')
        ->and($credentials->posAutCode)->toBe('SECRET')
        ->and($credentials->currency)->toBe('132')
        ->and($credentials->url)->toBe('https://gateway.com')
        ->and($credentials->languageMessages)->toBe('EN')
        ->and($credentials->fingerprintVersion)->toBe('1')
        ->and($credentials->is3DSec)->toBe('0')
        ->and($credentials->sandbox)->toBeFalse();
});
