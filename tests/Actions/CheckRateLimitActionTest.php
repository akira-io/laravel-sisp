<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Akira\Sisp\Models\RateLimit;

beforeEach(function (): void {
    config()->set('cache.default', 'array');
    config()->set('sisp.rate_limiting.enabled', true);
});

it('returns immediately when disabled', function (): void {
    config()->set('sisp.rate_limiting.enabled', false);
    resolve(CheckRateLimitAction::class)->handle('ip', '5.6.7.8', null, 10, 60);
    expect(true)->toBeTrue();
});

it('blocks after exceeding the limit and sets cache key', function (): void {
    $action = resolve(CheckRateLimitAction::class);

    $identifier = '1.1.1.1';
    $limit = 2;
    $window = 60;

    $action->handle('ip', $identifier, null, $limit, $window);
    $action->handle('ip', $identifier, null, $limit, $window);

    $thrown = false;
    try {
        $action->handle('ip', $identifier, null, $limit, $window);
    } catch (RateLimitExceededException) {
        $thrown = true;
    }
    expect($thrown)->toBeTrue();

    $rl = RateLimit::query()->where('identifier', $identifier)->first();
    expect($rl->hits)->toBe(3)
        ->and($rl->is_blocked)->toBeTrue();
});

it('resets when window elapsed then counts again', function (): void {
    $action = resolve(CheckRateLimitAction::class);
    $identifier = '2.2.2.2';
    $windowSeconds = 60;

    $rl = RateLimit::query()->create([
        'identifier' => $identifier,
        'limit_type' => 'ip',
        'context' => null,
        'hits' => 10,
        'limit' => 10,
        'window_seconds' => $windowSeconds,
        'reset_at' => now()->subMinute(),
        'is_blocked' => false,
    ]);

    $action->handle('ip', $identifier, null, 10, $windowSeconds);

    $fresh = RateLimit::query()->find($rl->id);
    expect($fresh->hits)->toBe(1)
        ->and($fresh->reset_at->isFuture())->toBeTrue();
});

it('increments an existing active window without creating duplicate records', function (): void {
    $action = resolve(CheckRateLimitAction::class);
    $identifier = '3.3.3.3';

    RateLimit::query()->create([
        'identifier' => $identifier,
        'limit_type' => 'ip',
        'context' => 'checkout',
        'hits' => 1,
        'limit' => 5,
        'window_seconds' => 60,
        'reset_at' => now()->addMinute(),
        'is_blocked' => false,
    ]);

    $action->handle('ip', $identifier, 'checkout', 5, 60);

    $rows = RateLimit::query()
        ->where('identifier', $identifier)
        ->where('limit_type', 'ip')
        ->where('context', 'checkout')
        ->get();

    expect($rows)->toHaveCount(1)
        ->and($rows->first()->hits)->toBe(2);
});
