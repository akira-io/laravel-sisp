<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;

final class InvalidSignatureException extends Exception
{
    public function __construct(
        string $message = 'Invalid signature',
        int $code = 403,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
