<?php

declare(strict_types=1);

namespace Akira\Sisp\Models;

use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Invoice extends Model
{
    public function getTable(): string
    {
        return config('sisp.tables.invoices', 'sisp_invoices');
    }

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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items(): HasMany
    {
        return $this->transaction->items();
    }
}
