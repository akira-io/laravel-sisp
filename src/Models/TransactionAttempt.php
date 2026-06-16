<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $transaction_id
 * @property-read int $attempt_number
 * @property-read string $merchant_ref
 * @property-read string $merchant_session
 * @property-read TransactionStatus $status
 * @property-read string|null $gateway_transaction_id
 * @property-read array<string, mixed>|null $payload
 * @property-read Carbon|null $submitted_at
 * @property-read Carbon|null $callback_received_at
 * @property-read Carbon|null $superseded_at
 */
final class TransactionAttempt extends Model
{
    use EncryptsAttributes;
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'transaction_id',
        'attempt_number',
        'merchant_ref',
        'merchant_session',
        'status',
        'gateway_transaction_id',
        'message_type',
        'response_code',
        'merchant_response',
        'fingerprint',
        'payload',
        'callback_payload',
        'failure_reason',
        'submitted_at',
        'callback_received_at',
        'superseded_at',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'status' => TransactionStatus::class,
            'payload' => 'array',
            'callback_payload' => 'array',
            'submitted_at' => 'datetime',
            'callback_received_at' => 'datetime',
            'superseded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    protected function encryptable(): array
    {
        return [
            'payload',
            'callback_payload',
        ];
    }
}
