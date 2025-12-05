<?php

declare(strict_types=1);

use Akira\Sisp\Models\Blacklist;

it('evaluates active and expired blacklist entries', function (): void {
    $active = Blacklist::query()->create([
        'type' => 'ip',
        'value' => '127.0.0.1',
        'severity' => 'low',
        'reason' => 'test',
        'expires_at' => null,
    ]);

    $expired = Blacklist::query()->create([
        'type' => 'ip',
        'value' => '127.0.0.2',
        'severity' => 'low',
        'reason' => 'test',
        'expires_at' => now()->subMinute(),
    ]);

    expect($active->isActive())->toBeTrue()
        ->and($active->isExpired())->toBeFalse()
        ->and($expired->isActive())->toBeFalse()
        ->and($expired->isExpired())->toBeTrue()
        ->and(Blacklist::query()->active()->count())->toBeGreaterThanOrEqual(1)
        ->and(Blacklist::query()->expired()->count())->toBeGreaterThanOrEqual(1);
});
