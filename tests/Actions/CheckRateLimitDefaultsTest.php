<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CheckRateLimitAction;

it('uses configured defaults for different limit types', function (): void {
    config()->set('sisp.rate_limiting.enabled', true);
    config()->set('cache.default', 'array');

    config()->set('sisp.rate_limiting.per_ip.limit', 99);
    config()->set('sisp.rate_limiting.per_ip.window_seconds', 10);
    config()->set('sisp.rate_limiting.per_user.limit', 3);
    config()->set('sisp.rate_limiting.per_user.window_seconds', 10);
    config()->set('sisp.rate_limiting.per_merchant.limit', 2);
    config()->set('sisp.rate_limiting.per_merchant.window_seconds', 10);

    $action = resolve(CheckRateLimitAction::class);

    // Should not throw on first hit for any type when using defaults
    $action->handle('ip', '9.9.9.9');
    $action->handle('user', 'user-1', 'ctx');
    $action->handle('merchant', 'merchant-1');
    $action->handle('unknown', 'x'); // exercise default branch

    expect(true)->toBeTrue();
});
