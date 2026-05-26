<?php

declare(strict_types=1);

use Akira\Sisp\Support\SispAmount;

it('converts amounts to SISP thousandths without float truncation', function (float|int|string $amount, int $expected): void {
    expect(SispAmount::toThousandths($amount))->toBe($expected);
})->with([
    'decimal string' => ['8.03', 8030],
    'decimal float' => [8.03, 8030],
    'already whole amount' => [1000, 1000000],
    'two decimals' => ['100.50', 100500],
    'three decimals' => ['0.001', 1],
    'rounds fourth decimal up' => ['8.0295', 8030],
    'keeps fourth decimal below half down' => ['8.0294', 8029],
]);
