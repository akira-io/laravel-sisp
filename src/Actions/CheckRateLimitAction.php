<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Exceptions\RateLimitExceededException;
use Akira\Sisp\Models\RateLimit;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        $identifier ??= request()->ip();

        $limit ??= $this->getDefaultLimit($limitType);
        $windowSeconds ??= $this->getDefaultWindow($limitType);
        $blockedKey = "rate_limit_blocked:{$limitType}:{$identifier}:{$context}";
        $lockKey = "rate_limit_lock:{$limitType}:{$identifier}:{$context}";

        throw_if(Cache::has($blockedKey), RateLimitExceededException::class, "Rate limit exceeded for {$limitType}: {$identifier}");

        $exceeded = false;

        try {
            Cache::lock($lockKey, 10)->block(5, function () use (&$exceeded, $limitType, $identifier, $context, $limit, $windowSeconds, $blockedKey): void {
                $exceeded = DB::transaction(
                    fn (): bool => $this->recordHit($limitType, $identifier, $context, $limit, $windowSeconds, $blockedKey)
                );
            });
        } catch (LockTimeoutException) {
            throw new RateLimitExceededException("Rate limit lock timeout for {$limitType}: {$identifier}");
        }

        throw_if($exceeded, RateLimitExceededException::class, "Rate limit exceeded for {$limitType}: {$identifier}. Limit: {$limit} requests per {$windowSeconds} seconds");
    }

    private function recordHit(
        string $limitType,
        string $identifier,
        ?string $context,
        int $limit,
        int $windowSeconds,
        string $blockedKey,
    ): bool {
        $rateLimit = RateLimit::query()
            ->where([
                'identifier' => $identifier,
                'limit_type' => $limitType,
                'context' => $context,
            ])
            ->lockForUpdate()
            ->first();

        $rateLimit ??= RateLimit::query()->create([
            'identifier' => $identifier,
            'limit_type' => $limitType,
            'context' => $context,
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

            return true;
        }

        return false;
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
