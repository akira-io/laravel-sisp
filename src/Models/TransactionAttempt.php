<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Database\Factories\TransactionAttemptFactory;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $transaction_id
 * @property int $attempt_number
 * @property string $merchant_ref
 * @property string $merchant_session
 * @property string $attempt_session
 * @property TransactionStatus $status
 * @property string|null $gateway_transaction_id
 * @property string|null $message_type
 * @property string|null $response_code
 * @property string|null $merchant_response
 * @property string|null $fingerprint
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $callback_payload
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $callback_received_at
 * @property \Illuminate\Support\Carbon|null $superseded_at
 * @property-read Transaction $transaction
 */
#[UseFactory(TransactionAttemptFactory::class)]
#[Fillable([
    'transaction_id',
    'attempt_number',
    'merchant_ref',
    'merchant_session',
    'attempt_session',
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
])]
final class TransactionAttempt extends Model
{
    use EncryptsAttributes;
    use HasFactory;

    public function getTable(): string
    {
        return config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isCurrent(): bool
    {
        return $this->superseded_at === null;
    }

    protected function casts(): array
    {
        return [
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

    protected function encryptable(): array
    {
        return [
            'payload',
            'callback_payload',
        ];
    }
}
