<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\TransactionData;

it('creates instance with all fields', function (): void {
    $transaction = new TransactionData(
        merchantRef: 'REF123',
        merchantSession: 'SESSION456',
        amount: 100.50,
        currency: '132',
        transactionCode: '1',
        payload: ['key' => 'value'],
    );

    expect($transaction)->toBeInstanceOf(TransactionData::class)
        ->and($transaction->merchantRef)->toBe('REF123')
        ->and($transaction->merchantSession)->toBe('SESSION456')
        ->and($transaction->amount)->toBe(100.50)
        ->and($transaction->currency)->toBe('132')
        ->and($transaction->transactionCode)->toBe('1')
        ->and($transaction->payload)->toBe(['key' => 'value']);
});

it('creates instance with only required fields', function (): void {
    $transaction = new TransactionData(
        merchantRef: 'REF123',
        merchantSession: 'SESSION456',
        amount: 100.50,
    );

    expect($transaction->merchantRef)->toBe('REF123')
        ->and($transaction->merchantSession)->toBe('SESSION456')
        ->and($transaction->amount)->toBe(100.50)
        ->and($transaction->currency)->toBe('132')
        ->and($transaction->transactionCode)->toBe('1')
        ->and($transaction->payload)->toBe([]);
});

it('creates instance from array with all fields', function (): void {
    $data = [
        'merchantRef' => 'REF789',
        'merchantSession' => 'SESSION012',
        'amount' => 250.75,
        'currency' => '978',
        'transactionCode' => '2',
        'payload' => ['data' => 'test'],
    ];

    $transaction = TransactionData::from($data);

    expect($transaction->merchantRef)->toBe('REF789')
        ->and($transaction->merchantSession)->toBe('SESSION012')
        ->and($transaction->amount)->toBe(250.75)
        ->and($transaction->currency)->toBe('978')
        ->and($transaction->transactionCode)->toBe('2')
        ->and($transaction->payload)->toBe(['data' => 'test']);
});

it('creates instance from array with default values', function (): void {
    $data = [
        'merchantRef' => 'REF123',
        'merchantSession' => 'SESSION456',
        'amount' => 100,
    ];

    $transaction = TransactionData::from($data);

    expect($transaction->merchantRef)->toBe('REF123')
        ->and($transaction->merchantSession)->toBe('SESSION456')
        ->and($transaction->amount)->toBe(100.0)
        ->and($transaction->currency)->toBe('132')
        ->and($transaction->transactionCode)->toBe('1')
        ->and($transaction->payload)->toBe([]);
});

it('converts string amount to float', function (): void {
    $data = [
        'merchantRef' => 'REF123',
        'merchantSession' => 'SESSION456',
        'amount' => '99.99',
    ];

    $transaction = TransactionData::from($data);

    expect($transaction->amount)->toBe(99.99)
        ->and($transaction->amount)->toBeFloat();
});

it('converts integer amount to float', function (): void {
    $data = [
        'merchantRef' => 'REF123',
        'merchantSession' => 'SESSION456',
        'amount' => 150,
    ];

    $transaction = TransactionData::from($data);

    expect($transaction->amount)->toBe(150.0)
        ->and($transaction->amount)->toBeFloat();
});

it('uses default currency when not provided', function (): void {
    $transaction = new TransactionData(
        merchantRef: 'REF123',
        merchantSession: 'SESSION456',
        amount: 100.50,
    );

    expect($transaction->currency)->toBe('132');
});

it('uses default transaction code when not provided', function (): void {
    $transaction = new TransactionData(
        merchantRef: 'REF123',
        merchantSession: 'SESSION456',
        amount: 100.50,
    );

    expect($transaction->transactionCode)->toBe('1');
});

it('uses empty array as default payload', function (): void {
    $transaction = new TransactionData(
        merchantRef: 'REF123',
        merchantSession: 'SESSION456',
        amount: 100.50,
    );

    expect($transaction->payload)->toBe([])
        ->and($transaction->payload)->toBeArray();
});

it('is readonly and immutable', function (): void {
    $transaction = new TransactionData(
        merchantRef: 'REF123',
        merchantSession: 'SESSION456',
        amount: 100.50,
    );

    expect($transaction->merchantRef)->toBe('REF123')
        ->and($transaction->amount)->toBe(100.50);
});
