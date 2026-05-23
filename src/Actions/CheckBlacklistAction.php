<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Exceptions\BlacklistedIdentifierException;
use Akira\Sisp\Models\Blacklist;
use Illuminate\Database\Eloquent\Builder;

final readonly class CheckBlacklistAction
{
    /**
     * @throws BlacklistedIdentifierException
     */
    public function handle(string $type = 'ip', ?string $value = null): void
    {
        $entry = Blacklist::query()
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where('type', $type)
            ->where('value', $value ?? request()->ip())
            ->first();

        if ($entry === null) {
            return;
        }

        throw new BlacklistedIdentifierException(
            "This {$type} is blacklisted: {$entry->getAttribute('reason')}"
        );
    }

    public function isBlacklisted(string $type, string $value): bool
    {
        return Blacklist::query()
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where('type', $type)
            ->where('value', $value)
            ->exists();
    }

    public function add(
        string $type,
        string $value,
        string $severity = 'medium',
        ?string $reason = null,
        ?string $notes = null,
        ?string $addedBy = null,
        ?int $expiresInMinutes = null
    ): Blacklist {
        return Blacklist::query()->create([
            'type' => $type,
            'value' => $value,
            'severity' => $severity,
            'reason' => $reason,
            'notes' => $notes,
            'added_by' => $addedBy,
            'expires_at' => $expiresInMinutes
                ? now()->addMinutes($expiresInMinutes)
                : null,
        ]);
    }

    public function remove(string $type, string $value): bool
    {
        return Blacklist::query()->where('type', $type)
            ->where('value', $value)
            ->delete() > 0;
    }
}
