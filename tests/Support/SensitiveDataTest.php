<?php

declare(strict_types=1);

use Akira\Sisp\Support\SensitiveData;

it('redacts sensitive keys at any depth', function (): void {
    expect(SensitiveData::redact([
        'posAutCode' => 'aut',
        'portal' => ['portal_password' => 'pass', 'portal_id' => 'id'],
        'geolocation' => ['maxmind_key' => 'mm', 'provider' => 'maxmind'],
        'headers' => ['authorization' => ['Bearer x'], 'accept' => ['json']],
        'cardNumber' => '4111',
        0 => 'plain',
    ]))->toBe([
        'posAutCode' => '[redacted]',
        'portal' => ['portal_password' => '[redacted]', 'portal_id' => 'id'],
        'geolocation' => ['maxmind_key' => '[redacted]', 'provider' => 'maxmind'],
        'headers' => ['authorization' => '[redacted]', 'accept' => ['json']],
        'cardNumber' => '[redacted]',
        0 => 'plain',
    ]);
});

it('redacts a single value by its key', function (): void {
    expect(SensitiveData::redactValue('posAutCode', 'aut'))->toBe('[redacted]')
        ->and(SensitiveData::redactValue('currency', '132'))->toBe('132')
        ->and(SensitiveData::redactValue('transaction_status', ['portal_password' => 'x']))->toBe(['portal_password' => '[redacted]']);
});
