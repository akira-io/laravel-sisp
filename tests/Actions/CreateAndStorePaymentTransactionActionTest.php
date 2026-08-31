<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Illuminate\Http\Request;

it('uses Laravel defer helper explicitly when scheduling invoice generation', function (): void {
    $reflection = new ReflectionClass(CreateAndStorePaymentTransactionAction::class);
    $fileName = $reflection->getFileName();

    expect($fileName)->toBeString();

    $source = file_get_contents((string) $fileName);

    expect($source)->toBeString()
        ->and($source)->toContain('use function Illuminate\Support\defer;')
        ->and($source)->toContain('defer(');
});

it('persists the buyer tax details from the incoming request', function (): void {
    $paymentRequest = PaymentRequest::from([
        'posID' => 'TEST_POS_001',
        'merchantRef' => 'REF-TAX-1',
        'merchantSession' => 'SESSION-TAX-1',
        'amount' => 1500,
        'currency' => '132',
        'is3DSec' => '1',
        'urlMerchantResponse' => 'https://example.com/callback',
        'languageMessages' => 'pt',
        'timeStamp' => '2026-08-31 10:00:00',
        'fingerprintversion' => '1',
        'transactionCode' => '1',
        'fingerprint' => 'fingerprint',
    ]);

    $request = Request::create('/sisp/pay', 'POST', [
        'customer_name' => 'Ana Silva',
        'customer_vat' => '253456789',
        'customer_tax_name' => 'PROLAR LDA',
        'customer_tax_entity_type' => 'company',
        'customer_tax_address' => 'Avenida Amilcar Cabral, Praia',
        'items' => [],
    ]);

    $transaction = resolve(CreateAndStorePaymentTransactionAction::class)->handle($paymentRequest, $request);

    expect($transaction->fresh()->customer_vat)->toBe('253456789')
        ->and($transaction->fresh()->customer_tax_name)->toBe('PROLAR LDA')
        ->and($transaction->fresh()->customer_tax_entity_type)->toBe('company')
        ->and($transaction->fresh()->customer_tax_address)->toBe('Avenida Amilcar Cabral, Praia');
});
