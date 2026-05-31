<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\TimeStampGeneratorAction;

it('generates a timestamp in SISP format', function (): void {
    $gen = resolve(TimeStampGeneratorAction::class);
    $ts = $gen();

    expect($ts)->toBeString()
        ->and(mb_strlen($ts))->toBe(19)
        ->and((bool) preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $ts))->toBeTrue();
});
