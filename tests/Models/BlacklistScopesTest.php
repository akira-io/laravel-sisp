<?php

declare(strict_types=1);

use Akira\Sisp\Models\Blacklist;

it('filters blacklist entries by type and severity', function (): void {
    Blacklist::query()->create([
        'type' => 'ip',
        'value' => '10.0.0.1',
        'severity' => 'high',
    ]);
    Blacklist::query()->create([
        'type' => 'email',
        'value' => 'user@example.test',
        'severity' => 'low',
    ]);

    expect(Blacklist::query()->byType('ip')->count())->toBe(1)
        ->and(Blacklist::query()->bySeverity('low')->count())->toBe(1);
});

