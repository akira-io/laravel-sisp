<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\CustomerData;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('builds the payment request data with the buyer tax details', function (): void {
    $data = PaymentRequestData::from([
        'amount' => 1500,
        'customer_vat' => '253456789',
        'customer_tax_name' => 'PROLAR LDA',
        'customer_tax_entity_type' => 'company',
        'customer_tax_address' => 'Avenida Amilcar Cabral, Praia',
    ]);

    expect($data->customerVat)->toBe('253456789')
        ->and($data->customerTaxName)->toBe('PROLAR LDA')
        ->and($data->customerTaxEntityType)->toBe('company')
        ->and($data->customerTaxAddress)->toBe('Avenida Amilcar Cabral, Praia');
});

it('leaves the tax details null when they are absent', function (): void {
    $data = PaymentRequestData::from(['amount' => 1500]);

    expect($data->customerVat)->toBeNull()
        ->and($data->customerTaxName)->toBeNull()
        ->and($data->customerTaxEntityType)->toBeNull()
        ->and($data->customerTaxAddress)->toBeNull();
});

it('never reports the tax details as missing three d secure fields', function (): void {
    expect(PaymentRequestData::from(['amount' => 1500])->getMissingThreeDSecureFields())
        ->not->toContain('customer_vat')
        ->not->toContain('customer_tax_name')
        ->not->toContain('customer_tax_entity_type')
        ->not->toContain('customer_tax_address');
});

it('carries the buyer tax details through the customer data', function (): void {
    $customer = CustomerData::from([
        'customer_name' => 'Ana Silva',
        'customer_vat' => '253456789',
        'customer_tax_name' => 'PROLAR LDA',
        'customer_tax_entity_type' => 'company',
        'customer_tax_address' => 'Avenida Amilcar Cabral, Praia',
    ]);

    expect($customer->vat)->toBe('253456789')
        ->and($customer->taxName)->toBe('PROLAR LDA')
        ->and($customer->taxEntityType)->toBe('company')
        ->and($customer->taxAddress)->toBe('Avenida Amilcar Cabral, Praia')
        ->and($customer->toArray())->toMatchArray([
            'customer_vat' => '253456789',
            'customer_tax_name' => 'PROLAR LDA',
            'customer_tax_entity_type' => 'company',
            'customer_tax_address' => 'Avenida Amilcar Cabral, Praia',
        ]);
});
