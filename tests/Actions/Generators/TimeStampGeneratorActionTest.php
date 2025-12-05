<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Generators\TimeStampGeneratorAction;

it('generates a timestamp in YmdHis format', function (): void {
    $gen = resolve(TimeStampGeneratorAction::class);
    $ts = $gen();

    expect($ts)->toBeString()
        ->and(mb_strlen($ts))->toBe(14)
        ->and((bool) preg_match('/^\d{14}$/', $ts))->toBeTrue();
});
