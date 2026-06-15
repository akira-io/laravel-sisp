<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use RuntimeException;

final class DuplicatePaymentIdentifierException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The generated SISP payment identifiers already exist.');
    }
}
