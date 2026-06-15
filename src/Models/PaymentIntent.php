<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $idempotency_key
 * @property-read int|null $transaction_id
 * @property-read string $status
 * @property-read string|null $failure_reason
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
final class PaymentIntent extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'idempotency_key',
        'transaction_id',
        'status',
        'failure_reason',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.payment_intents', 'sisp_payment_intents');
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
