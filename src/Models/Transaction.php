<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read  int $id
 * @property-read  TransactionStatus $status
 * @property-read  array $payload
 * @property-read  string $customer_email
 * @property-read  string $merchant_ref
 * @property-read  int $transaction_id
 * @property-read  string $locale
 * @property-read  string $customer_name
 * @property-read  string $customer_phone
 * @property-read  string $customer_country
 * @property-read  string $customer_city
 * @property-read  string $customer_address
 * @property-read  int|float $amount
 * @property-read  string $formatted_amount
 * @property-read  Invoice|null $invoice
 */
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
        'locale',
        'cancelled_at',
        'refunded_at',
        'customer_postal_code',
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

    protected function getFormattedAmountAttribute(): string
    {
        $formatted = number_format($this->amount, 0, ',', '.');

        return "{$formatted} ECV";
    }

    protected function encryptable(): array
    {
        return [
            'payload',
        ];
    }
}
