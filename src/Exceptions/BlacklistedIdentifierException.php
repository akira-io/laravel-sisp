<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * An HTTP exception, so the framework answers 403 itself: JSON with the
 * message for API clients, the error page for a browser.
 */
final class BlacklistedIdentifierException extends HttpException
{
    public function __construct(
        string $message = 'This identifier is blacklisted',
        int $code = 403,
        ?Exception $previous = null
    ) {
        parent::__construct($code, $message, $previous, [], $code);
    }
}
