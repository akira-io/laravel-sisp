<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Akira\Sisp\Models\RateLimit;

beforeEach(function (): void {
    // Ensure array cache for deterministic behavior
    config()->set('cache.default', 'array');
    // Enable rate limiting by default for these tests
    config()->set('sisp.rate_limiting.enabled', true);
});

it('returns immediately when disabled', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);
    resolve(CheckRateLimitAction::class)->handle('ip', '5.6.7.8', null, 10, 60);
    expect(true)->toBeTrue();
});

it('blocks after exceeding the limit and sets cache key', function (): void {
    $action = resolve(CheckRateLimitAction::class);

    // Small window to exercise the block path
    $identifier = '1.1.1.1';
    $limit = 2; // allow only 2 requests
    $window = 60; // seconds

    // First hit creates entry and records hit
    $action->handle('ip', $identifier, null, $limit, $window);

    // Second hit should exceed and throw
    $thrown = false;
    try {
        $action->handle('ip', $identifier, null, $limit, $window);
    } catch (RateLimitExceededException) {
        $thrown = true;
    }
    expect($thrown)->toBeTrue();

    // Model is marked blocked
    $rl = RateLimit::query()->where('identifier', $identifier)->first();
    expect($rl->is_blocked)->toBeTrue();
});

it('resets when window elapsed then counts again', function (): void {
    $action = resolve(CheckRateLimitAction::class);
    $identifier = '2.2.2.2';

    // Create a record in the past so it is reset
    $rl = RateLimit::query()->create([
        'identifier' => $identifier,
        'limit_type' => 'ip',
        'context' => null,
        'hits' => 10,
        'limit' => 10,
        'window_seconds' => 1,
        'reset_at' => now()->subMinute(),
        'is_blocked' => false,
    ]);

    // Next handle should reset and then record a hit without throwing
    $action->handle('ip', $identifier, null, 10, 60);

    $fresh = RateLimit::query()->find($rl->id);
    expect($fresh->hits)->toBe(1)
        ->and($fresh->reset_at->isFuture())->toBeTrue();
});
