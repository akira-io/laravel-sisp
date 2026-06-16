<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Illuminate\Database\QueryException;
use Throwable;

final class UniqueConstraintViolation
{
    public static function causedBy(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        if (in_array($sqlState, ['23000', '23505'], true)) {
            return true;
        }

        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, 'duplicate key')
            || str_contains($message, 'unique constraint failed');
    }
}
