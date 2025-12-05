<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\InvoiceData;
use Carbon\Carbon;

it('creates instance with all fields', function (): void {
    $invoiceDate = Illuminate\Support\Facades\Date::parse('2023-12-04');
    $dueDate = Illuminate\Support\Facades\Date::parse('2023-12-14');

    $invoice = new InvoiceData(
        invoice_number: 'INV-2023-001',
        invoice_date: $invoiceDate,
        due_date: $dueDate,
        notes: 'Thank you for your business',
        metadata: ['tax_id' => '123456'],
    );

    expect($invoice)->toBeInstanceOf(InvoiceData::class)
        ->and($invoice->invoice_number)->toBe('INV-2023-001')
        ->and($invoice->invoice_date)->toBe($invoiceDate)
        ->and($invoice->due_date)->toBe($dueDate)
        ->and($invoice->notes)->toBe('Thank you for your business')
        ->and($invoice->metadata)->toBe(['tax_id' => '123456']);
});

it('creates instance with only required fields', function (): void {
    $invoiceDate = Illuminate\Support\Facades\Date::parse('2023-12-04');

    $invoice = new InvoiceData(
        invoice_number: 'INV-2023-002',
        invoice_date: $invoiceDate,
    );

    expect($invoice->invoice_number)->toBe('INV-2023-002')
        ->and($invoice->invoice_date)->toBe($invoiceDate)
        ->and($invoice->due_date)->toBeNull()
        ->and($invoice->notes)->toBeNull()
        ->and($invoice->metadata)->toBeNull();
});

it('creates instance from array with Carbon dates', function (): void {
    $invoiceDate = Illuminate\Support\Facades\Date::parse('2023-12-04');
    $dueDate = Illuminate\Support\Facades\Date::parse('2023-12-14');

    $data = [
        'invoice_number' => 'INV-2023-003',
        'invoice_date' => $invoiceDate,
        'due_date' => $dueDate,
        'notes' => 'Payment terms: Net 10',
        'metadata' => ['customer_id' => '456'],
    ];

    $invoice = InvoiceData::from($data);

    expect($invoice->invoice_number)->toBe('INV-2023-003')
        ->and($invoice->invoice_date)->toBe($invoiceDate)
        ->and($invoice->due_date)->toBe($dueDate)
        ->and($invoice->notes)->toBe('Payment terms: Net 10')
        ->and($invoice->metadata)->toBe(['customer_id' => '456']);
});

it('creates instance from array with string dates', function (): void {
    $data = [
        'invoice_number' => 'INV-2023-004',
        'invoice_date' => '2023-12-04',
        'due_date' => '2023-12-14',
    ];

    $invoice = InvoiceData::from($data);

    expect($invoice->invoice_number)->toBe('INV-2023-004')
        ->and($invoice->invoice_date)->toBeInstanceOf(Carbon::class)
        ->and($invoice->invoice_date->format('Y-m-d'))->toBe('2023-12-04')
        ->and($invoice->due_date)->toBeInstanceOf(Carbon::class)
        ->and($invoice->due_date->format('Y-m-d'))->toBe('2023-12-14');
});

it('creates instance from array without due date', function (): void {
    $data = [
        'invoice_number' => 'INV-2023-005',
        'invoice_date' => '2023-12-04',
    ];

    $invoice = InvoiceData::from($data);

    expect($invoice->invoice_number)->toBe('INV-2023-005')
        ->and($invoice->invoice_date)->toBeInstanceOf(Carbon::class)
        ->and($invoice->due_date)->toBeNull()
        ->and($invoice->notes)->toBeNull()
        ->and($invoice->metadata)->toBeNull();
});

it('converts to array correctly', function (): void {
    $invoiceDate = Illuminate\Support\Facades\Date::parse('2023-12-04');
    $dueDate = Illuminate\Support\Facades\Date::parse('2023-12-14');

    $invoice = new InvoiceData(
        invoice_number: 'INV-2023-006',
        invoice_date: $invoiceDate,
        due_date: $dueDate,
        notes: 'Thank you',
        metadata: ['key' => 'value'],
    );

    $array = $invoice->toArray();

    expect($array)->toBeArray()
        ->toHaveKeys(['invoice_number', 'invoice_date', 'due_date', 'notes', 'metadata'])
        ->and($array['invoice_number'])->toBe('INV-2023-006')
        ->and($array['invoice_date'])->toBe($invoiceDate)
        ->and($array['due_date'])->toBe($dueDate)
        ->and($array['notes'])->toBe('Thank you')
        ->and($array['metadata'])->toBe(['key' => 'value']);
});

it('converts to array with null optional fields', function (): void {
    $invoiceDate = Illuminate\Support\Facades\Date::parse('2023-12-04');

    $invoice = new InvoiceData(
        invoice_number: 'INV-2023-007',
        invoice_date: $invoiceDate,
    );

    $array = $invoice->toArray();

    expect($array['invoice_number'])->toBe('INV-2023-007')
        ->and($array['invoice_date'])->toBe($invoiceDate)
        ->and($array['due_date'])->toBeNull()
        ->and($array['notes'])->toBeNull()
        ->and($array['metadata'])->toBeNull();
});

it('handles various date formats', function (): void {
    $formats = [
        '2023-12-04',
        '2023-12-04 10:30:00',
        '04-12-2023',
        '12/04/2023',
    ];

    foreach ($formats as $format) {
        $data = [
            'invoice_number' => 'INV-TEST',
            'invoice_date' => $format,
        ];

        $invoice = InvoiceData::from($data);

        expect($invoice->invoice_date)->toBeInstanceOf(Carbon::class);
    }
});

it('is readonly and immutable', function (): void {
    $invoiceDate = Illuminate\Support\Facades\Date::parse('2023-12-04');

    $invoice = new InvoiceData(
        invoice_number: 'INV-2023-008',
        invoice_date: $invoiceDate,
    );

    expect($invoice->invoice_number)->toBe('INV-2023-008')
        ->and($invoice->invoice_date)->toBe($invoiceDate);
});
