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

it('converts amounts to cents for canonical transaction storage', function (float|int|string $amount, int $expected): void {
    expect(SispAmount::toCents($amount))->toBe($expected);
})->with([
    'decimal string' => ['8.03', 803],
    'decimal float' => [8.03, 803],
    'whole amount' => [1000, 100000],
    'rounds half cent up' => ['8.025', 803],
    'keeps below half cent down' => ['8.024', 802],
]);
