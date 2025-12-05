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
