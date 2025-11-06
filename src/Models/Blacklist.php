<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Model;

final class Blacklist extends Model
{
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

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
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
}
