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

it('decodes a stored payload, treating null as empty and refusing one it cannot read', function (): void {
    expect(LegacyPayload::decodeStored(null))->toBe([])
        ->and(LegacyPayload::decodeStored('{"posID":"90001"}'))->toBe(['posID' => '90001'])
        ->and(fn (): array => LegacyPayload::decodeStored('not a payload'))
        ->toThrow(LogicException::class, 'The stored transaction payload could not be decoded.');
});

it('refuses a refund history it cannot read', function (): void {
    expect(LegacyPayload::refundHistory([]))->toBe([])
        ->and(LegacyPayload::refundHistory(['refunds' => '[{"amount":1}]']))->toBe([['amount' => 1]])
        ->and(fn (): array => LegacyPayload::refundHistory(['refunds' => 'corrupted']))
        ->toThrow(LogicException::class, 'The stored refund history could not be decoded.');
});

it('tells whether an undecodable value may still hold refund history', function (string $value, bool $mayHold): void {
    expect(LegacyPayload::mayHoldRefunds($value))->toBe($mayHold);
})->with([
    'names refunds' => ['{"refunds":[{"amount":1}', true],
    'sealed envelope' => fn (): array => [Crypt::encryptString('{"posID":"90001"}'), true],
    'plain text' => ['not a payload', false],
    'base64 that is not an envelope' => [base64_encode('{"posID":"90001"}'), false],
    'not base64' => ['%%%', false],
]);
