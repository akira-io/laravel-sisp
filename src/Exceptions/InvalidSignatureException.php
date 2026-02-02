<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class InvalidSignatureException extends AccessDeniedHttpException
{
    public function __construct(string $message = 'Invalid callback signature.', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
