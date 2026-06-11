<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Enums\InvoiceStatus;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @property string $customer_name
 * @property CarbonInterface $created_at
 * @property-read  string|null $pdf_path
 * @property-read  InvoiceStatus $status
 * @property-read  Transaction|null $transaction
 * @property-read  array $items
 * @property-read  int $items_count
 * @property-read  CarbonInterface $invoice_date
 * @property-read  CarbonInterface|null $due_date
 * @property-read  string $invoice_number
 * @property-read  CarbonInterface $created_at
 * @property-read  string|null $pdf_url
 * @property-read  array $metadata
 * @property-read  string $customer_name
 * @property-read  CarbonInterface $updated_at
 */
#[Fillable([
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
])]
final class Invoice extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    #[Override]
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

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'metadata' => 'array',
        ];
    }

    protected function getPdfUrlAttribute(): ?string
    {
        if (! $this->pdf_path) {
            return null;
        }

        $configuredUrl = $this->configuredPdfUrl();

        if ($configuredUrl !== null) {
            return $configuredUrl;
        }

        $disk = config('sisp.invoice.disk', 'public');
        $storage = Storage::disk($disk);

        if ($disk === 's3') {
            $expirationHours = resolve(LoadConfig::class)->getInvoiceTemporaryUrlExpirationHours();

            return $storage->temporaryUrl($this->pdf_path, now()->addHours($expirationHours));
        }

        return $storage->url($this->pdf_path);
    }

    private function configuredPdfUrl(): ?string
    {
        $generator = config('sisp.invoice.pdf_url_generator');

        if (is_string($generator) && $generator !== '') {
            try {
                $generator = resolve($generator);
            } catch (BindingResolutionException) {
                return null;
            }
        }

        if (! is_callable($generator)) {
            return null;
        }

        /** @var callable(self): mixed $generator */
        $url = $generator($this);

        return is_string($url) && $url !== '' ? $url : null;
    }
}
