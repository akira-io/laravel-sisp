<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use RuntimeException;
use Throwable;

final class UnableToGenerateUniquePaymentIdentifiersException extends RuntimeException
{
    public function __construct(int $attempts, ?Throwable $previous = null)
    {
        parent::__construct(
            "Unable to generate unique SISP payment identifiers after {$attempts} attempts.",
            previous: $previous,
        );
    }
}
