<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class BlacklistedIdentifierException extends Exception
{
    public function __construct(
        string $message = 'This identifier is blacklisted',
        int $code = 403,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Answer with 403 instead of surfacing as a server error: JSON for API
     * clients, the framework's error page for a browser.
     */
    public function render(Request $request): JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], $this->getCode());
        }

        throw new HttpException($this->getCode(), $this->getMessage(), $this);
    }
}
