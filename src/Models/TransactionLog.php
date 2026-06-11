<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'transaction_id',
    'source',
    'changed_attributes',
    'old_values',
    'new_values',
])]
final class TransactionLog extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('sisp.tables.transaction_logs', 'sisp_transaction_logs');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    protected function casts(): array
    {
        return [
            'changed_attributes' => 'array',
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }
}
