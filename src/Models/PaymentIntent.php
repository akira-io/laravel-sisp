<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Database\Factories\PaymentIntentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $idempotency_key
 * @property int|null $transaction_id
 * @property string $status
 * @property string|null $failure_reason
 * @property-read Transaction|null $transaction
 */
#[UseFactory(PaymentIntentFactory::class)]
#[Fillable([
    'idempotency_key',
    'transaction_id',
    'status',
    'failure_reason',
])]
final class PaymentIntent extends Model
{
    use HasFactory;

    public function getTable(): string
    {
        return config('sisp.tables.payment_intents', 'sisp_payment_intents');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    protected function casts(): array
    {
        return [
            'transaction_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
