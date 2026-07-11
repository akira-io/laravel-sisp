<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Middleware;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Exceptions\BlacklistedIdentifierException;
use Akira\Sisp\Exceptions\RateLimitExceededException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnforceSispRateLimits
{
    public function __construct(
        private CheckRateLimitAction $checkRateLimit,
        private CheckBlacklistAction $checkBlacklist,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        try {
            $this->checkBlacklist->handle('ip', $ip);

            if (config('sisp.rate_limiting.per_ip.enabled')) {
                $this->checkRateLimit->handle(
                    $ip,
                    'ip',
                    limit: (int) config('sisp.rate_limiting.per_ip.limit'),
                    windowSeconds: (int) config('sisp.rate_limiting.per_ip.window_seconds')
                );
            }
        } catch (BlacklistedIdentifierException|RateLimitExceededException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => $e->getCode(),
            ], $e->getCode());
        }

        return $next($request);
    }
}
