<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class InvalidSignatureException extends AccessDeniedHttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            message: $message ?? 'Invalid signature',
        );
    }
}
