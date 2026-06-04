<?php

declare(strict_types=1);

use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Request;

it('stores request metadata with basic fields', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);

    $transaction = Transaction::factory()->create();

    $server = [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15',
        'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
        'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
        'HTTP_REFERER' => 'https://example.test',
    ];
    $request = Request::create('/test', 'GET', server: $server);

    $meta = $action->handle($request, $transaction);

    expect($meta)->toBeInstanceOf(RequestMetadata::class)
        ->and($meta->transaction_id)->toBe($transaction->id)
        ->and($meta->ip_address)->toBe('127.0.0.1')
        ->and(is_string($meta->browser))->toBeTrue();
});

it('stores sanitized request payload for diagnostics', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create(
        uri: '/sisp/callback?ref=MR-DIAG',
        method: 'POST',
        parameters: [
            'merchantRespMerchantRef' => 'MR-DIAG',
            'merchantRespMerchantSession' => 'MS-DIAG',
            'merchantRespPurchaseAmount' => '10',
            'resultFingerPrint' => 'signed-fingerprint',
            'card_number' => '4111111111111111',
            'api_key' => 'api-secret',
            'pin' => '1234',
            'nested' => [
                'token' => 'secret-token',
                'safe' => 'visible',
            ],
        ],
        server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_AUTHORIZATION' => 'Bearer secret',
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
        ],
    );

    $meta = $action->handle($request, $transaction);

    expect($meta->custom_metadata)->toMatchArray([
        'method' => 'POST',
        'path' => 'sisp/callback',
        'query' => ['ref' => 'MR-DIAG'],
        'payload' => [
            'merchantRespMerchantRef' => 'MR-DIAG',
            'merchantRespMerchantSession' => 'MS-DIAG',
            'merchantRespPurchaseAmount' => '10',
            'resultFingerPrint' => 'signed-fingerprint',
            'card_number' => '[redacted]',
            'api_key' => '[redacted]',
            'pin' => '[redacted]',
            'nested' => [
                'token' => '[redacted]',
                'safe' => 'visible',
            ],
        ],
    ])
        ->and($meta->custom_metadata['headers']['authorization'])->toBe('[redacted]');
});

it('detects browser/OS/device/mobile flags from user-agent variants', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);

    $transaction = Transaction::factory()->create();

    $variants = [
        'Mozilla/5.0 (Linux; Android 10; SM-A205G) AppleWebKit/537.36 Chrome/86.0 Mobile',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
        'Mozilla/5.0 (Windows NT 10.0; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Edge/18.19041',
    ];

    foreach ($variants as $ua) {
        $server = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => $ua,
            'HTTP_ACCEPT_LANGUAGE' => 'en',
        ];
        $request = Request::create('/test', 'GET', server: $server);
        $meta = $action->handle($request, $transaction);
        expect($meta->browser)->not->toBe('')
            ->and($meta->os)->not->toBe('')
            ->and(is_bool($meta->is_mobile))->toBeTrue();
    }
});

it('handles public ip when location driver is missing', function (): void {
    config()->set('location.driver');

    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $server = [
        'REMOTE_ADDR' => '8.8.8.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'HTTP_ACCEPT_LANGUAGE' => 'en',
    ];
    $request = Request::create('/test', 'GET', server: $server);

    $meta = $action->handle($request, $transaction);

    expect($meta->country_code)->toBeNull()
        ->and($meta->country_name)->toBeNull();
});

it('stores neutral advanced security metadata without external detection support', function (): void {
    config()->set('sisp.security.detect_vpn', true);
    config()->set('sisp.security.detect_proxy', true);
    config()->set('sisp.security.calculate_risk_score', true);
    config()->set('sisp.security.block_vpn_proxy', true);

    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $request = Request::create('/test', 'GET', server: [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);

    $meta = $action->handle($request, $transaction);

    expect($meta->is_vpn)->toBeFalse()
        ->and($meta->is_proxy)->toBeFalse()
        ->and($meta->risk_score)->toBe(0)
        ->and($meta->risk_reason)->toBeNull();
});

it('detects tablet (iPad) and marks mobile true', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $server = [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPad; CPU OS 15_0 like Mac OS X) AppleWebKit/605.1.15 Version/15.0 Mobile/15E148 Safari/604.1',
        'HTTP_ACCEPT_LANGUAGE' => 'en',
    ];
    $request = Request::create('/test', 'GET', server: $server);
    $meta = $action->handle($request, $transaction);

    expect($meta->device_type)->toBe('tablet')
        ->and($meta->is_mobile)->toBeTrue()
        ->and(in_array($meta->os, ['iOS', 'macOS', 'Unknown']))->toBeTrue();
});

it('detects Linux OS from user agent', function (): void {
    $action = resolve(StoreRequestMetadataAction::class);
    $transaction = Transaction::factory()->create();

    $server = [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
        'HTTP_ACCEPT_LANGUAGE' => 'en',
    ];
    $request = Request::create('/test', 'GET', server: $server);
    $meta = $action->handle($request, $transaction);

    expect($meta->os)->toBe('Linux');
});
