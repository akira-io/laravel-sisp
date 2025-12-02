<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Model;

final class Blacklist extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
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

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function active($query)
    {
        return $query->where(function ($q): void {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function expired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function byType($query, string $type)
    {
        return $query->where('type', $type);
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function bySeverity($query, string $severity)
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
