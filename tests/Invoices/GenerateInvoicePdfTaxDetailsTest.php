<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Storage;

final class TaxDetailsPdfGenerator implements PdfGeneratorContract
{
    public ?DtoInvoiceData $lastInvoice = null;

    public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
    {
        $this->lastInvoice = $invoice;

        return '%PDF-TAX%';
    }

    public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
    {
        return '%PDF-SAVED%';
    }
}

function generatePdfFor(Transaction $transaction): TaxDetailsPdfGenerator
{
    $pdfGenerator = new TaxDetailsPdfGenerator();
    app()->instance(PdfGeneratorContract::class, $pdfGenerator);
    Storage::fake('public');

    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    resolve(GenerateInvoicePdfAction::class)->handle($invoice->refresh());

    return $pdfGenerator;
}

it('renders the buyer tax id and prefers the tax name and tax address', function (): void {
    $pdfGenerator = generatePdfFor(Transaction::factory()->create([
        'customer_name' => 'Ana Silva',
        'customer_address' => 'Rua da Praia 12',
        'customer_vat' => '253456789',
        'customer_tax_name' => 'PROLAR LDA',
        'customer_tax_entity_type' => 'company',
        'customer_tax_address' => 'Avenida Amilcar Cabral, Praia',
    ]));

    $buyer = $pdfGenerator->lastInvoice->buyer;

    expect($buyer->vatNumber)->toBe('253456789')
        ->and($buyer->name)->toBe('PROLAR LDA')
        ->and($buyer->address)->toBe('Avenida Amilcar Cabral, Praia');
});

it('falls back to the contact name and address when there are no tax details', function (): void {
    $pdfGenerator = generatePdfFor(Transaction::factory()->create([
        'customer_name' => 'Ana Silva',
        'customer_address' => 'Rua da Praia 12',
        'customer_vat' => null,
        'customer_tax_name' => null,
        'customer_tax_address' => null,
    ]));

    $buyer = $pdfGenerator->lastInvoice->buyer;

    expect($buyer->vatNumber)->toBe('')
        ->and($buyer->name)->toBe('Ana Silva')
        ->and($buyer->address)->toBe('Rua da Praia 12');
});
