<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class Blacklist extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'type',
        'value',
        'reason',
        'severity',
        'notes',
        'added_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.blacklist', 'sisp_blacklist');
    }

    public function isActive(): bool
    {
        if ($this->expires_at === null) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return ! $this->isActive();
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    #[Scope]
    protected function expired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    #[Scope]
    protected function byType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    #[Scope]
    protected function bySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }
}
