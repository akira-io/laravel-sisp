<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;

final class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
