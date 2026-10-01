<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * An HTTP exception, so the framework answers 429 itself: JSON with the
 * message for API clients, the error page for a browser, Retry-After on both.
 */
final class RateLimitExceededException extends HttpException
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        ?Exception $previous = null,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        $headers = $retryAfterSeconds === null ? [] : ['Retry-After' => (string) $retryAfterSeconds];

        parent::__construct($code, $message, $previous, $headers, $code);
    }
}
