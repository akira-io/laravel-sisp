<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Transaction extends Model
{
    use EncryptsAttributes;
    use HasFactory;

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
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_country',
        'customer_city',
        'customer_address',
        'cancelled_at',
        'refunded_at',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.transactions', 'sisp_transactions');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'transaction_id');
    }

    public function getFormattedAmountAttribute(): string
    {
        $formatted = number_format($this->amount, 0, ',', '.');

        return "{$formatted} ECV";
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

    protected function encryptable(): array
    {
        return [
            'payload',
            'merchant_response',
        ];
    }
}
