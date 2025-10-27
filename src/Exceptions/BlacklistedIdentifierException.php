<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;

final class BlacklistedIdentifierException extends Exception
{
    public function __construct(
        string $message = 'This identifier is blacklisted',
        int $code = 403,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
