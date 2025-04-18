<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class UnableToDecryptDataException extends Exception
{
    /**
     * Create a new UnableToDecryptDataException instance.
     */
    public function __construct()
    {
        parent::__construct(
            message: __('Unable to decrypt data.'),
            code: Response::HTTP_BAD_REQUEST,
        );
    }
}
