<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Enums\InvoiceStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read  string|null $pdf_url
 * @property-read  InvoiceStatus $status
 * @property-read  Transaction $transaction
 * @property-read  array $items
 * @property-read  int $items_count
 * @property-read  CarbonInterface $invoice_date
 * @property-read  CarbonInterface|null $due_date
 * @property-read  string $invoice_number
 */
final class Invoice extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'customer_name',
        'customer_email',
        'customer_city',
        'customer_address',
        'customer_country',
        'notes',
        'pdf_path',
        'metadata',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'status' => InvoiceStatus::class,
        'metadata' => 'array',
    ];

    protected $appends = [
        'pdf_url',
    ];

    public function getTable(): string
    {
        return config('sisp.tables.invoices', 'sisp_invoices');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items(): HasMany
    {
        return $this->transaction->items();
    }

    protected function getPdfUrlAttribute(): ?string
    {
        if (! $this->pdf_path) {
            return null;
        }

        $disk = config('sisp.invoice.disk', 'public');

        if ($disk === 's3') {
            $expirationHours = resolve(LoadConfig::class)->getInvoiceTemporaryUrlExpirationHours();

            return Storage::disk($disk)->temporaryUrl($this->pdf_path, now()->addHours($expirationHours));
        }

        return Storage::disk($disk)->url($this->pdf_path);
    }
}
