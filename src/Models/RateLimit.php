<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class RateLimit extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'identifier',
        'limit_type',
        'context',
        'hits',
        'limit',
        'window_seconds',
        'reset_at',
        'is_blocked',
        'blocked_until',
    ];

    protected $casts = [
        'hits' => 'integer',
        'limit' => 'integer',
        'window_seconds' => 'integer',
        'is_blocked' => 'boolean',
        'reset_at' => 'datetime',
        'blocked_until' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.rate_limits', 'sisp_rate_limits');
    }

    public function isLimitExceeded(): bool
    {
        return $this->hits >= $this->limit;
    }

    public function recordHit(): self
    {
        $this->increment('hits');

        return $this;
    }

    public function reset(): self
    {
        $this->update([
            'hits' => 0,
            'reset_at' => now()->addSeconds($this->window_seconds),
            'is_blocked' => false,
            'blocked_until' => null,
        ]);

        return $this;
    }

    public function block(?int $durationSeconds = null): self
    {
        $this->update([
            'is_blocked' => true,
            'blocked_until' => $durationSeconds
                ? now()->addSeconds($durationSeconds)
                : null,
        ]);

        return $this;
    }

    #[Scope]
    protected function expired(Builder $query): Builder
    {
        return $query->where('reset_at', '<', now());
    }

    #[Scope]
    protected function blocked(Builder $query): Builder
    {
        return $query->where('is_blocked', true)
            ->where(function (Builder $q): void {
                $q->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            });
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('reset_at', '>', now());
    }
}
