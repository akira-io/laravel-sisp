<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Illuminate\Database\QueryException;

final class UniqueConstraintViolation
{
    public static function causedBy(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = mb_strtolower($exception->getMessage());

        return $sqlState === '23505'
            || ($sqlState === '23000' && $driverCode === 1062)
            || ($sqlState === '23000' && str_contains($message, 'unique constraint failed'));
    }
}
