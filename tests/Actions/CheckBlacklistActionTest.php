<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Exceptions\BlacklistedIdentifierException;
use Akira\Sisp\Models\Blacklist;

it('returns silently when not blacklisted', function (): void {
    resolve(CheckBlacklistAction::class)->handle('ip', '1.2.3.4');
    expect(true)->toBeTrue();
});

it('throws when identifier is blacklisted (active scope)', function (): void {
    Blacklist::query()->create([
        'type' => 'ip',
        'value' => '9.9.9.9',
        'reason' => 'blocked for abuse',
        'severity' => 'high',
        'expires_at' => now()->addDay(),
    ]);

    expect(fn () => resolve(CheckBlacklistAction::class)->handle('ip', '9.9.9.9'))
        ->toThrow(BlacklistedIdentifierException::class);
});

it('can add and remove a blacklist entry via action helpers', function (): void {
    $action = resolve(CheckBlacklistAction::class);
    $entry = $action->add('email', 'user@example.com', 'medium', 'spam', 'note', 'tester', 60);

    expect($action->isBlacklisted('email', 'user@example.com'))->toBeTrue();

    $removed = $action->remove('email', 'user@example.com');
    expect($removed)->toBeTrue()
        ->and($action->isBlacklisted('email', 'user@example.com'))->toBeFalse();
});

