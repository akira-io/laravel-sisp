<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;

final class MissingThreeDSecureDataException extends Exception
{
    /**
     * @param  array<string>  $missingFields
     */
    public function __construct(array $missingFields)
    {
        $fields = implode(', ', $missingFields);
        parent::__construct(
            "3D Secure is enabled but required customer data is missing: {$fields}"
        );
    }
}
