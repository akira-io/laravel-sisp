<?php

declare(strict_types=1);

use Akira\Sisp\Support\LegacyPayload;
use Illuminate\Support\Facades\Crypt;

it('decodes the shapes a legacy payload is stored in', function (mixed $stored): void {
    expect(LegacyPayload::decode($stored))->toBe(['posID' => '90001']);
})->with([
    'array' => [['posID' => '90001']],
    'json string' => ['{"posID":"90001"}'],
    'json encoded twice' => [json_encode('{"posID":"90001"}')],
    'encrypted json' => fn (): string => Crypt::encryptString('{"posID":"90001"}'),
    'envelope inside an envelope' => fn (): string => Crypt::encryptString(Crypt::encryptString('{"posID":"90001"}')),
]);

it('returns null for a value that never becomes an array', function (mixed $stored): void {
    expect(LegacyPayload::decode($stored))->toBeNull();
})->with([
    'null' => [null],
    'integer' => [42],
    'json scalar' => ['42'],
    'json null' => ['null'],
    'truncated json' => ['{"posID":"90001"'],
    'plain text' => ['not a payload'],
]);

it('stops unwrapping after a bounded number of layers', function (): void {
    $stored = '{"posID":"90001"}';

    for ($layer = 0; $layer < 8; $layer++) {
        $stored = json_encode($stored);
    }

    expect(LegacyPayload::decode($stored))->toBeNull();
});

it('reads the refunds list out of a decoded payload', function (): void {
    expect(LegacyPayload::refunds([]))->toBe([])
        ->and(LegacyPayload::refunds(['refunds' => [['amount' => 1]]]))->toBe([['amount' => 1]])
        ->and(LegacyPayload::refunds(['refunds' => '[{"amount":1}]']))->toBe([['amount' => 1]])
        ->and(LegacyPayload::refunds(['refunds' => 'corrupted']))->toBeNull();
});
