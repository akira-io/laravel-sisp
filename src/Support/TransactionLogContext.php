<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

final class TransactionLogContext
{
    private static array $sources = [];

    public static function run(string $source, callable $callback): mixed
    {
        self::$sources[] = $source;

        try {
            return $callback();
        } finally {
            array_pop(self::$sources);
        }
    }

    public static function current(): string
    {
        $source = end(self::$sources);

        return is_string($source) && $source !== '' ? $source : 'model';
    }
}
