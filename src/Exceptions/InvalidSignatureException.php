<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvalidSignatureException extends AccessDeniedHttpException
{
}
