<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Actions\LogTransactionChangesAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Support\SispAmount;
use Akira\Sisp\Traits\EncryptsAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @property-read  int $id
 * @property-read  TransactionStatus $status
 * @property-read  array<string, mixed> $payload
 * @property-read  string|null $customer_email
 * @property-read  string $merchant_ref
 * @property-read  string $merchant_session
 * @property-read  string|null $transaction_id
 * @property-read  string|null $message_type
 * @property-read  string|null $response_code
 * @property-read  string|null $merchant_response
 * @property-read  string|null $fingerprint
 * @property-read  string $locale
 * @property-read  string|null $customer_name
 * @property-read  string|null $customer_phone
 * @property-read  string|null $customer_country
 * @property-read  string|null $customer_city
 * @property-read  string|null $customer_address
 * @property-read  int|float $amount
 * @property-read  int $amount_cents
 * @property-read  Carbon|null $created_at
 * @property-read  Carbon|null $updated_at
 */
final class Transaction extends Model
{
    use EncryptsAttributes;
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'merchant_ref',
        'merchant_session',
        'amount',
        'amount_cents',
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

    /**
     * @return HasMany<TransactionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id');
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'transaction_id');
    }

    /**
     * @return HasMany<TransactionLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class, 'transaction_id');
    }

    /**
     * @return HasMany<TransactionAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(TransactionAttempt::class, 'transaction_id');
    }

    /**
     * @return HasMany<PaymentIntent, $this>
     */
    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class, 'transaction_id');
    }

    /**
     * @return HasOne<TransactionAttempt, $this>
     */
    public function currentAttempt(): HasOne
    {
        return $this->hasOne(TransactionAttempt::class, 'transaction_id')
            ->whereNull('superseded_at')
            ->latestOfMany();
    }

    protected static function booted(): void
    {
        self::created(static function (Transaction $transaction): void {
            $referencesTable = config('sisp.tables.transaction_references', 'sisp_transaction_references');

            if (! Schema::hasTable($referencesTable)) {
                return;
            }

            DB::table($referencesTable)->insert([
                'merchant_ref' => $transaction->merchant_ref,
                'transaction_id' => $transaction->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        self::updated(static function (Transaction $transaction): void {
            resolve(LogTransactionChangesAction::class)->handle($transaction);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'amount_cents' => 'integer',
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

    protected function amount(): Attribute
    {
        return Attribute::make(
            set: fn (float|int|string $amount): array => [
                'amount' => (float) $amount,
                'amount_cents' => SispAmount::toCents($amount),
            ],
        );
    }

    protected function amountCents(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $amountCents, array $attributes): int => $amountCents !== null
                ? (int) $amountCents
                : SispAmount::toCents($attributes['amount'] ?? 0),
        );
    }
}
