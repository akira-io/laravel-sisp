<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Schema;

it('stores the buyer tax details on the transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'customer_vat' => '253456789',
        'customer_tax_name' => 'PROLAR LDA',
        'customer_tax_entity_type' => 'company',
        'customer_tax_address' => 'Avenida Amilcar Cabral, Praia',
    ]);

    expect($transaction->fresh()->customer_vat)->toBe('253456789')
        ->and($transaction->fresh()->customer_tax_name)->toBe('PROLAR LDA')
        ->and($transaction->fresh()->customer_tax_entity_type)->toBe('company')
        ->and($transaction->fresh()->customer_tax_address)->toBe('Avenida Amilcar Cabral, Praia');
});

it('accepts a transaction without any tax details', function (): void {
    expect(Transaction::factory()->create()->fresh()->customer_vat)->toBeNull();
});

it('has the tax columns on the invoices table', function (): void {
    expect(Schema::hasColumns(config('sisp.tables.invoices', 'sisp_invoices'), [
        'customer_vat',
        'customer_tax_name',
        'customer_tax_entity_type',
        'customer_tax_address',
    ]))->toBeTrue();
});
