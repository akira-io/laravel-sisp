<?php

declare(strict_types=1);

use Akira\Sisp\Models\RateLimit;

it('applies expired and active scopes correctly', function (): void {
    $expired = RateLimit::query()->create([
        'identifier' => 'id-expired',
        'limit_type' => 'ip',
        'context' => null,
        'hits' => 0,
        'limit' => 10,
        'window_seconds' => 60,
        'reset_at' => now()->subMinute(),
        'is_blocked' => false,
    ]);

    $active = RateLimit::query()->create([
        'identifier' => 'id-active',
        'limit_type' => 'ip',
        'context' => null,
        'hits' => 0,
        'limit' => 10,
        'window_seconds' => 60,
        'reset_at' => now()->addMinute(),
        'is_blocked' => false,
    ]);

    expect(RateLimit::query()->expired()->pluck('identifier')->all())
        ->toContain('id-expired')
        ->and(RateLimit::query()->active()->pluck('identifier')->all())
        ->toContain('id-active');
});

it('applies blocked scope with null or future blocked_until', function (): void {
    $nullBlocked = RateLimit::query()->create([
        'identifier' => 'id-null-blocked',
        'limit_type' => 'ip',
        'context' => null,
        'hits' => 0,
        'limit' => 10,
        'window_seconds' => 60,
        'reset_at' => now()->addMinute(),
        'is_blocked' => true,
        'blocked_until' => null,
    ]);

    $futureBlocked = RateLimit::query()->create([
        'identifier' => 'id-future-blocked',
        'limit_type' => 'ip',
        'context' => null,
        'hits' => 0,
        'limit' => 10,
        'window_seconds' => 60,
        'reset_at' => now()->addMinute(),
        'is_blocked' => true,
        'blocked_until' => now()->addMinute(),
    ]);

    expect(RateLimit::query()->blocked()->pluck('identifier')->all())
        ->toContain('id-null-blocked')
        ->toContain('id-future-blocked');
});
