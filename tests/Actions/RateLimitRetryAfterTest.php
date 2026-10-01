<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Illuminate\Support\Facades\Cache;

function refusedRateLimit(callable $callback): ?RateLimitExceededException
{
    try {
        $callback();
    } catch (RateLimitExceededException $exception) {
        return $exception;
    }

    return null;
}

it('answers every refusal with the seconds left to wait, not only the first', function (): void {
    config()->set('sisp.rate_limiting.enabled', true);
    config()->set('sisp.rate_limiting.per_ip.limit', 1);
    config()->set('sisp.rate_limiting.per_ip.window_seconds', 600);

    $check = resolve(CheckRateLimitAction::class);

    $check->handle('ip', '203.0.113.7', 'callback');

    $first = refusedRateLimit(fn () => $check->handle('ip', '203.0.113.7', 'callback'));
    $second = refusedRateLimit(fn () => $check->handle('ip', '203.0.113.7', 'callback'));

    expect($first?->retryAfterSeconds)->toBe(600)
        ->and($second?->retryAfterSeconds)->toBe(600)
        ->and($second?->getHeaders())->toHaveKey('Retry-After');
});

it('omits the retry hint for a block cached by an earlier release', function (): void {
    config()->set('sisp.rate_limiting.enabled', true);

    Cache::put('rate_limit_blocked:ip:198.51.100.4:callback', true, 600);

    $exception = refusedRateLimit(
        fn () => resolve(CheckRateLimitAction::class)->handle('ip', '198.51.100.4', 'callback'),
    );

    expect($exception)->not->toBeNull()
        ->and($exception?->retryAfterSeconds)->toBeNull()
        ->and($exception?->getHeaders())->not->toHaveKey('Retry-After');
});
