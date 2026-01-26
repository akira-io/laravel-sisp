<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

final class InvalidSignatureException extends Exception
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            message: $message ?? 'Invalid signature',
            code: Response::HTTP_FORBIDDEN,
        );
    }
}
