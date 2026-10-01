<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait ThrottlesToolCalls
{
    private const array FALLBACK_LIMITS = ['gateway' => ['per_caller' => 10, 'global' => 60], 'destructive' => ['per_caller' => 5, 'global' => 20]];

    private function throttleToolCall(Request $request, string $bucket, string $subject): ?Response
    {
        $limits = [
            "sisp-mcp-{$bucket}:".($request->user()?->getAuthIdentifier() ?? 'local') => $this->limit($bucket, 'per_caller'),
            "sisp-mcp-{$bucket}:all" => $this->limit($bucket, 'global'),
        ];

        $limits = array_filter($limits, fn (int $perMinute): bool => $perMinute > 0);

        foreach ($limits as $key => $perMinute) {
            if (RateLimiter::tooManyAttempts($key, $perMinute)) {
                return Response::error("Too many {$subject}. Retry in ".RateLimiter::availableIn($key).' seconds.');
            }
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key);
        }

        return null;
    }

    private function limit(string $bucket, string $scope): int
    {
        return (int) config("sisp.mcp.rate_limits.{$bucket}.{$scope}", self::FALLBACK_LIMITS[$bucket][$scope] ?? 0);
    }
}
