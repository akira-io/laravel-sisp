<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use RuntimeException;

final class UnknownMerchantCredentialsException extends RuntimeException
{
    public function __construct(public readonly string $posId)
    {
        parent::__construct(
            "No SISP credentials are known for posID {$posId}. Bind Akira\\Sisp\\Contracts\\TransactionCredentialsResolver to resolve the credentials of each merchant."
        );
    }
}
