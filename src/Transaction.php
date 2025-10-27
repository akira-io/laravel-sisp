<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\TransactionItem;
use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Transaction extends Model
{
    use EncryptsAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'merchant_ref',
        'merchant_session',
        'amount',
        'currency',
        'status',
        'transaction_code',
        'transaction_id',
        'message_type',
        'response_code',
        'merchant_response',
        'fingerprint',
        'payload',
        'cancelled_at',
        'refunded_at',
    ];

    protected function encryptable(): array
    {
        return [
            'payload',
            'merchant_response',
        ];
    }

    public function getTable(): string
    {
        return config('sisp.tables.transactions', 'sisp_transactions');
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'amount' => 'float',
            'status' => TransactionStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'transaction_id');
    }
}
