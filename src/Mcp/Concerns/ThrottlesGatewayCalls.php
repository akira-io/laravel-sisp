<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait ThrottlesGatewayCalls
{
    private function throttleGatewayCall(Request $request): ?Response
    {
        $caller = 'sisp-mcp-gateway:'.($request->user()?->getAuthIdentifier() ?? 'local');
        $limits = [
            $caller => (int) config('sisp.mcp.gateway_rate_limit.per_caller', 10),
            'sisp-mcp-gateway:all' => (int) config('sisp.mcp.gateway_rate_limit.global', 60),
        ];

        foreach ($limits as $key => $perMinute) {
            if (RateLimiter::tooManyAttempts($key, $perMinute)) {
                return Response::error('Too many SISP status requests. Retry in '.RateLimiter::availableIn($key).' seconds.');
            }
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key);
        }

        return null;
    }
}
