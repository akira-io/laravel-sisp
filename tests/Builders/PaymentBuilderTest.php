<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionCode;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('builds payment request data fluently', function (): void {
    $data = Sisp::payment()
        ->amount(150.5)
        ->merchantRef('MR-BUILDER')
        ->merchantSession('MS-BUILDER')
        ->timeStamp('2026-01-01 00:00:00')
        ->currency('132')
        ->transactionCode(TransactionCode::purchase)
        ->token('tok')
        ->entityCode('ent')
        ->referenceNumber('ref')
        ->locale('pt')
        ->customerEmail('buyer@example.test')
        ->customerCountry('CV')
        ->customerCity('Praia')
        ->customerAddress('Rua Teste')
        ->customerPostalCode('7600')
        ->customerPhone('+238 000 0000')
        ->toData();

    expect($data)->toBeInstanceOf(PaymentRequestData::class)
        ->and($data->amount)->toBe(150.5)
        ->and($data->merchantRef)->toBe('MR-BUILDER')
        ->and($data->merchantSession)->toBe('MS-BUILDER')
        ->and($data->timeStamp)->toBe('2026-01-01 00:00:00')
        ->and($data->currency)->toBe('132')
        ->and($data->transactionCode)->toBe('1')
        ->and($data->token)->toBe('tok')
        ->and($data->entityCode)->toBe('ent')
        ->and($data->referenceNumber)->toBe('ref')
        ->and($data->locale)->toBe('pt')
        ->and($data->customerEmail)->toBe('buyer@example.test')
        ->and($data->customerCountry)->toBe('CV')
        ->and($data->customerCity)->toBe('Praia')
        ->and($data->customerAddress)->toBe('Rua Teste')
        ->and($data->customerPostalCode)->toBe('7600')
        ->and($data->customerPhone)->toBe('+238 000 0000');
});

it('builds a signed payment request through the builder', function (): void {
    $request = Sisp::payment()
        ->amount(99.0)
        ->merchantRef('MR-BUILD')
        ->merchantSession('MS-BUILD')
        ->currency('132')
        ->transactionCode('1')
        ->build();

    expect($request)->toBeInstanceOf(PaymentRequest::class)
        ->and($request->merchantRef)->toBe('MR-BUILD')
        ->and($request->amount)->toBe(99.0)
        ->and($request->fingerprint)->not->toBe('');
});

it('accepts a transaction code string', function (): void {
    $data = Sisp::payment()->amount(10.0)->transactionCode('7')->toData();

    expect($data->transactionCode)->toBe('7');
});

it('rejects building without an amount', function (): void {
    Sisp::payment()->toData();
})->throws(LogicException::class, 'A payment amount greater than zero is required.');

it('rejects building with a non-positive amount', function (): void {
    Sisp::payment()->amount(0.0)->toData();
})->throws(LogicException::class, 'A payment amount greater than zero is required.');
