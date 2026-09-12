<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Support\SispAmount;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read  int $id
 * @property-read  int $transaction_id
 * @property-read  float $amount
 * @property-read  int $amount_cents
 * @property-read  string|null $reason
 * @property-read  array<string, mixed>|null $request
 * @property-read  Transaction $transaction
 */
#[Fillable([
    'transaction_id',
    'amount',
    'amount_cents',
    'reason',
    'request',
])]
final class Refund extends Model
{
    public function getTable(): string
    {
        return config('sisp.tables.refunds', 'sisp_refunds');
    }

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'amount_cents' => 'integer',
            'request' => 'array',
        ];
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            set: fn (float|int|string $amount): array => [
                'amount' => (float) $amount,
                'amount_cents' => SispAmount::toCents($amount),
            ],
        );
    }
}
