<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final readonly class InvoiceData
{
    public function __construct(
        public string $invoice_number,
        public CarbonInterface $invoice_date,
        public ?CarbonInterface $due_date = null,
        public ?string $notes = null,
        public ?array $metadata = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            invoice_number: $data['invoice_number'],
            invoice_date: $data['invoice_date'] instanceof CarbonInterface
                ? $data['invoice_date']
                : Date::parse($data['invoice_date']),
            due_date: isset($data['due_date'])
                ? ($data['due_date'] instanceof CarbonInterface ? $data['due_date'] : Date::parse($data['due_date']))
                : null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
