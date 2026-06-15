<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Illuminate\Database\QueryException;

final class UniqueConstraintViolation
{
    public static function causedBy(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $message = mb_strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            || str_contains($message, 'unique')
            || str_contains($message, 'duplicate');
    }
}
