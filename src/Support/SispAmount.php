<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

final readonly class SispAmount
{
    public static function toThousandths(float|int|string $amount): int
    {
        $decimal = self::decimalString($amount);

        if ($decimal === null) {
            return (int) round((float) $amount * 1000);
        }

        return self::decimalStringToThousandths($decimal);
    }

    private static function decimalString(float|int|string $amount): ?string
    {
        if (is_float($amount)) {
            return number_format($amount, 10, '.', '');
        }

        $decimal = mb_trim((string) $amount);

        if ($decimal === '') {
            return null;
        }

        return preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/', $decimal) === 1
            ? $decimal
            : null;
    }

    private static function decimalStringToThousandths(string $decimal): int
    {
        $sign = 1;

        if (str_starts_with($decimal, '-')) {
            $sign = -1;
            $decimal = mb_substr($decimal, 1);
        } elseif (str_starts_with($decimal, '+')) {
            $decimal = mb_substr($decimal, 1);
        }

        [$units, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        $units = $units === '' ? '0' : $units;
        $fraction = mb_str_pad($fraction, 4, '0');

        $thousandths = ((int) $units * 1000) + (int) mb_substr($fraction, 0, 3);

        if ((int) $fraction[3] >= 5) {
            $thousandths++;
        }

        return $sign * $thousandths;
    }
}
