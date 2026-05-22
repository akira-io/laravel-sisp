<?php

declare(strict_types=1);

use Akira\Sisp\Models\RateLimit;

it('records hits, checks limits, resets and blocks', function (): void {
    $rl = RateLimit::query()->create([
        'identifier' => 'ip:127.0.0.1',
        'limit_type' => 'ip',
        'context' => null,
        'hits' => 0,
        'limit' => 2,
        'window_seconds' => 60,
        'reset_at' => now()->addSeconds(60),
        'is_blocked' => false,
    ]);

    expect($rl->isLimitExceeded())->toBeFalse();

    $rl->recordHit();
    $rl->recordHit();

    $rl->refresh();
    expect($rl->hits)->toBe(2)
        ->and($rl->isLimitExceeded())->toBeFalse();

    $rl->recordHit()->refresh();
    expect($rl->hits)->toBe(3)
        ->and($rl->isLimitExceeded())->toBeTrue();

    $rl->block(30)->refresh();
    expect($rl->is_blocked)->toBeTrue();

    $rl->reset()->refresh();
    expect($rl->hits)->toBe(0)
        ->and($rl->is_blocked)->toBeFalse();

    $rl->block()->refresh();
    expect($rl->is_blocked)->toBeTrue()
        ->and($rl->blocked_until)->toBeNull();
});
