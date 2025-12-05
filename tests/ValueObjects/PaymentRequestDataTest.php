<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\PaymentRequestData;

it('creates instance with all fields', function (): void {
    $data = new PaymentRequestData(
        amount: 100.50,
        merchantRef: 'REF123',
        merchantSession: 'SESSION456',
        timeStamp: '20231204120000',
        currency: '132',
        transactionCode: '1',
        token: 'TOKEN123',
        entityCode: 'ENTITY123',
        referenceNumber: 'REF123',
    );

    expect($data)->toBeInstanceOf(PaymentRequestData::class)
        ->and($data->amount)->toBe(100.50)
        ->and($data->merchantRef)->toBe('REF123')
        ->and($data->merchantSession)->toBe('SESSION456')
        ->and($data->timeStamp)->toBe('20231204120000')
        ->and($data->currency)->toBe('132')
        ->and($data->transactionCode)->toBe('1')
        ->and($data->token)->toBe('TOKEN123')
        ->and($data->entityCode)->toBe('ENTITY123')
        ->and($data->referenceNumber)->toBe('REF123');
});

it('creates instance with only required field', function (): void {
    $data = new PaymentRequestData(amount: 250.75);

    expect($data->amount)->toBe(250.75)
        ->and($data->merchantRef)->toBeNull()
        ->and($data->merchantSession)->toBeNull()
        ->and($data->timeStamp)->toBeNull()
        ->and($data->currency)->toBeNull()
        ->and($data->transactionCode)->toBeNull()
        ->and($data->token)->toBeNull()
        ->and($data->entityCode)->toBeNull()
        ->and($data->referenceNumber)->toBeNull();
});

it('creates instance from array with all fields', function (): void {
    $array = [
        'amount' => 100.50,
        'merchantRef' => 'REF123',
        'merchantSession' => 'SESSION456',
        'timeStamp' => '20231204120000',
        'currency' => '132',
        'transactionCode' => '1',
        'token' => 'TOKEN123',
        'entityCode' => 'ENTITY123',
        'referenceNumber' => 'REF123',
    ];

    $data = PaymentRequestData::from($array);

    expect($data->amount)->toBe(100.50)
        ->and($data->merchantRef)->toBe('REF123')
        ->and($data->merchantSession)->toBe('SESSION456')
        ->and($data->timeStamp)->toBe('20231204120000')
        ->and($data->currency)->toBe('132')
        ->and($data->transactionCode)->toBe('1')
        ->and($data->token)->toBe('TOKEN123')
        ->and($data->entityCode)->toBe('ENTITY123')
        ->and($data->referenceNumber)->toBe('REF123');
});

it('creates instance from array with only amount', function (): void {
    $array = ['amount' => 150.25];

    $data = PaymentRequestData::from($array);

    expect($data->amount)->toBe(150.25)
        ->and($data->merchantRef)->toBeNull()
        ->and($data->merchantSession)->toBeNull()
        ->and($data->timeStamp)->toBeNull()
        ->and($data->currency)->toBeNull()
        ->and($data->transactionCode)->toBeNull()
        ->and($data->token)->toBeNull()
        ->and($data->entityCode)->toBeNull()
        ->and($data->referenceNumber)->toBeNull();
});

it('converts string amount to float', function (): void {
    $array = ['amount' => '199.99'];

    $data = PaymentRequestData::from($array);

    expect($data->amount)->toBe(199.99)
        ->and($data->amount)->toBeFloat();
});

it('converts integer amount to float', function (): void {
    $array = ['amount' => 200];

    $data = PaymentRequestData::from($array);

    expect($data->amount)->toBe(200.0)
        ->and($data->amount)->toBeFloat();
});

it('handles partial data from array', function (): void {
    $array = [
        'amount' => 100,
        'merchantRef' => 'REF123',
        'currency' => '132',
    ];

    $data = PaymentRequestData::from($array);

    expect($data->amount)->toBe(100.0)
        ->and($data->merchantRef)->toBe('REF123')
        ->and($data->currency)->toBe('132')
        ->and($data->merchantSession)->toBeNull()
        ->and($data->timeStamp)->toBeNull()
        ->and($data->transactionCode)->toBeNull();
});

it('is readonly and immutable', function (): void {
    $data = new PaymentRequestData(
        amount: 100.50,
        merchantRef: 'REF123',
    );

    expect($data->amount)->toBe(100.50)
        ->and($data->merchantRef)->toBe('REF123');
});
