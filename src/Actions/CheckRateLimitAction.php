<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Exceptions\RateLimitExceededException;
use Akira\Sisp\Models\RateLimit;
use Illuminate\Support\Facades\Cache;

final readonly class CheckRateLimitAction
{
    public function handle(

        string $limitType = 'ip',
        ?string $identifier = null,
        ?string $context = null,
        ?int $limit = null,
        ?int $windowSeconds = null
    ): void {
        if (! config('sisp.rate_limiting.enabled')) {
            return;
        }

        $identifier ?? request()->ip();

        $limit ??= $this->getDefaultLimit($limitType);
        $windowSeconds ??= $this->getDefaultWindow($limitType);
        $blockedKey = "rate_limit_blocked:{$limitType}:{$identifier}:{$context}";

        throw_if(Cache::has($blockedKey), RateLimitExceededException::class, "Rate limit exceeded for {$limitType}: {$identifier}");

        $rateLimit = RateLimit::query()->firstOrCreate([
            'identifier' => $identifier,
            'limit_type' => $limitType,
            'context' => $context,
        ], [
            'hits' => 0,
            'limit' => $limit,
            'window_seconds' => $windowSeconds,
            'reset_at' => now()->addSeconds($windowSeconds),
        ]);

        if ($rateLimit->reset_at->isPast()) {
            $rateLimit->reset();
        }

        $rateLimit->recordHit();

        if ($rateLimit->isLimitExceeded()) {
            $rateLimit->block($windowSeconds);
            Cache::put($blockedKey, true, $windowSeconds);

            throw new RateLimitExceededException(
                "Rate limit exceeded for {$limitType}: {$identifier}. Limit: {$limit} requests per {$windowSeconds} seconds"
            );
        }
    }

    private function getDefaultLimit(string $limitType): int
    {
        return match ($limitType) {
            'ip' => (int) config('sisp.rate_limiting.per_ip.limit', 100),
            'merchant' => (int) config('sisp.rate_limiting.per_merchant.limit', 500),
            'user' => (int) config('sisp.rate_limiting.per_user.limit', 50),
            default => 100,
        };
    }

    private function getDefaultWindow(string $limitType): int
    {
        return match ($limitType) {
            'ip' => (int) config('sisp.rate_limiting.per_ip.window_seconds', 3600),
            'merchant' => (int) config('sisp.rate_limiting.per_merchant.window_seconds', 3600),
            'user' => (int) config('sisp.rate_limiting.per_user.window_seconds', 3600),
            default => 3600,
        };
    }
}
