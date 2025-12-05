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
        $request = \Illuminate\Http\Request::create('/test', 'GET', server: $server);
        $meta = $action->handle($request, $transaction);
        expect($meta->browser)->not->toBe('')
            ->and($meta->os)->not->toBe('')
            ->and(is_bool($meta->is_mobile))->toBeTrue();
    }
});
