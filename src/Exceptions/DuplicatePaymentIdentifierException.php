<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use RuntimeException;
use Throwable;

final class DuplicatePaymentIdentifierException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('The generated SISP payment identifiers already exist.', previous: $previous);
    }
}
