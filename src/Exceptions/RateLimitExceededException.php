<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class RateLimitExceededException extends Exception
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        int $code = 429,
        ?Exception $previous = null,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Answer with 429 instead of surfacing as a server error: JSON for API
     * clients, the framework's error page for a browser.
     */
    public function render(Request $request): JsonResponse
    {
        $headers = $this->retryAfterSeconds === null ? [] : ['Retry-After' => (string) $this->retryAfterSeconds];

        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], $this->getCode(), $headers);
        }

        throw new HttpException($this->getCode(), $this->getMessage(), $this, $headers);
    }
}
