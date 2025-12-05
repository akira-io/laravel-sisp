<?php

declare(strict_types=1);

use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\Transaction;

it('honors table config and relation casts', function (): void {
    config()->set('sisp.tables.request_metadata', 'sisp_request_metadata');

    $t = Transaction::factory()->create();
    $meta = RequestMetadata::query()->create([
        'transaction_id' => $t->id,
        'ip_address' => '8.8.8.8',
        'user_agent' => 'Mozilla',
        'is_vpn' => true,
        'is_proxy' => false,
        'is_mobile' => false,
        'risk_score' => 5,
        'custom_metadata' => ['k' => 'v'],
    ]);

    expect($meta->getTable())->toBe('sisp_request_metadata')
        ->and($meta->transaction->id)->toBe($t->id)
        ->and($meta->is_vpn)->toBeTrue()
        ->and($meta->custom_metadata)->toBe(['k' => 'v']);
});
