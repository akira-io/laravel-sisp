<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price_cents',
        'total_price_cents',
        'description',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'total_price_cents' => 'integer',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.transaction_items', 'sisp_transaction_items');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    protected function getUnitPriceAttribute(): float
    {
        return $this->unit_price_cents / 100;
    }

    protected function setUnitPriceAttribute(float $value): void
    {
        $this->attributes['unit_price_cents'] = (int) round($value * 100);
    }

    protected function getTotalPriceAttribute(): float
    {
        return $this->total_price_cents / 100;
    }

    protected function setTotalPriceAttribute(float $value): void
    {
        $this->attributes['total_price_cents'] = (int) round($value * 100);
    }
}
