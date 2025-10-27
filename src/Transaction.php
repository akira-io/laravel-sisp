<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return type(config('sisp.table_name'))->asString();
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'amount' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
