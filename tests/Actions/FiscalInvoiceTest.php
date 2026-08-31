<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Models\Transaction;

it('copies the buyer tax details from the transaction to the invoice', function (): void {
    $transaction = Transaction::factory()->create([
        'customer_vat' => '253456789',
        'customer_tax_name' => 'PROLAR LDA',
        'customer_tax_entity_type' => 'company',
        'customer_tax_address' => 'Avenida Amilcar Cabral, Praia',
    ]);

    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);

    expect($invoice->customer_vat)->toBe('253456789')
        ->and($invoice->customer_tax_name)->toBe('PROLAR LDA')
        ->and($invoice->customer_tax_entity_type)->toBe('company')
        ->and($invoice->customer_tax_address)->toBe('Avenida Amilcar Cabral, Praia');
});

it('generates an invoice without tax details when the transaction has none', function (): void {
    $transaction = Transaction::factory()->create(['customer_vat' => null]);

    expect(resolve(GenerateInvoiceAction::class)->handle($transaction)->customer_vat)->toBeNull();
});
