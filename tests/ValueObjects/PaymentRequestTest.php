<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\PaymentRequest;

it('creates instance with all fields', function (): void {
    $payment = new PaymentRequest(
        posID: 'POS123',
        merchantRef: 'REF456',
        merchantSession: 'SESSION789',
        amount: 100.50,
        currency: '132',
        is3DSec: '1',
        urlMerchantResponse: 'https://example.com/callback',
        languageMessages: 'en',
        timeStamp: '20231204120000',
        fingerprintversion: '1',
        transactionCode: '1',
        fingerprint: 'abc123fingerprint',
        token: 'TOKEN123',
        entityCode: 'ENTITY123',
        referenceNumber: 'REF123',
    );

    expect($payment)->toBeInstanceOf(PaymentRequest::class)
        ->and($payment->posID)->toBe('POS123')
        ->and($payment->merchantRef)->toBe('REF456')
        ->and($payment->merchantSession)->toBe('SESSION789')
        ->and($payment->amount)->toBe(100.50)
        ->and($payment->currency)->toBe('132')
        ->and($payment->is3DSec)->toBe('1')
        ->and($payment->urlMerchantResponse)->toBe('https://example.com/callback')
        ->and($payment->languageMessages)->toBe('en')
        ->and($payment->timeStamp)->toBe('20231204120000')
        ->and($payment->fingerprintversion)->toBe('1')
        ->and($payment->transactionCode)->toBe('1')
        ->and($payment->fingerprint)->toBe('abc123fingerprint')
        ->and($payment->token)->toBe('TOKEN123')
        ->and($payment->entityCode)->toBe('ENTITY123')
        ->and($payment->referenceNumber)->toBe('REF123');
});

it('creates instance with only required fields', function (): void {
    $payment = new PaymentRequest(
        posID: 'POS123',
        merchantRef: 'REF456',
        merchantSession: 'SESSION789',
        amount: 100.50,
        currency: '132',
        is3DSec: '1',
        urlMerchantResponse: 'https://example.com/callback',
        languageMessages: 'en',
        timeStamp: '20231204120000',
        fingerprintversion: '1',
        transactionCode: '1',
        fingerprint: 'abc123',
    );

    expect($payment->posID)->toBe('POS123')
        ->and($payment->amount)->toBe(100.50)
        ->and($payment->token)->toBe('')
        ->and($payment->entityCode)->toBe('')
        ->and($payment->referenceNumber)->toBe('');
});

it('creates instance from array with all fields', function (): void {
    $data = [
        'posID' => 'POS123',
        'merchantRef' => 'REF456',
        'merchantSession' => 'SESSION789',
        'amount' => 100.50,
        'currency' => '132',
        'is3DSec' => '1',
        'urlMerchantResponse' => 'https://example.com/callback',
        'languageMessages' => 'en',
        'timeStamp' => '20231204120000',
        'fingerprintversion' => '1',
        'transactionCode' => '1',
        'fingerprint' => 'abc123',
        'token' => 'TOKEN123',
        'entityCode' => 'ENTITY123',
        'referenceNumber' => 'REF123',
    ];

    $payment = PaymentRequest::from($data);

    expect($payment->posID)->toBe('POS123')
        ->and($payment->merchantRef)->toBe('REF456')
        ->and($payment->merchantSession)->toBe('SESSION789')
        ->and($payment->amount)->toBe(100.50)
        ->and($payment->token)->toBe('TOKEN123')
        ->and($payment->entityCode)->toBe('ENTITY123')
        ->and($payment->referenceNumber)->toBe('REF123');
});

it('creates instance from array without optional fields', function (): void {
    $data = [
        'posID' => 'POS123',
        'merchantRef' => 'REF456',
        'merchantSession' => 'SESSION789',
        'amount' => 200,
        'currency' => '132',
        'is3DSec' => '1',
        'urlMerchantResponse' => 'https://example.com/callback',
        'languageMessages' => 'pt',
        'timeStamp' => '20231204120000',
        'fingerprintversion' => '1',
        'transactionCode' => '1',
        'fingerprint' => 'abc123',
    ];

    $payment = PaymentRequest::from($data);

    expect($payment->amount)->toBe(200)
        ->and($payment->token)->toBe('')
        ->and($payment->entityCode)->toBe('')
        ->and($payment->referenceNumber)->toBe('');
});

it('converts to array correctly', function (): void {
    $payment = new PaymentRequest(
        posID: 'POS123',
        merchantRef: 'REF456',
        merchantSession: 'SESSION789',
        amount: 100.50,
        currency: '132',
        is3DSec: '1',
        urlMerchantResponse: 'https://example.com/callback',
        languageMessages: 'en',
        timeStamp: '20231204120000',
        fingerprintversion: '1',
        transactionCode: '1',
        fingerprint: 'abc123',
        token: 'TOKEN123',
        entityCode: 'ENTITY123',
        referenceNumber: 'REF123',
    );

    $array = $payment->toArray();

    expect($array)->toBeArray()
        ->toHaveKeys([
            'posID',
            'merchantRef',
            'merchantSession',
            'amount',
            'currency',
            'is3DSec',
            'urlMerchantResponse',
            'languageMessages',
            'timeStamp',
            'fingerprintversion',
            'transactionCode',
            'fingerprint',
            'token',
            'entityCode',
            'referenceNumber',
        ])
        ->and($array['posID'])->toBe('POS123')
        ->and($array['merchantRef'])->toBe('REF456')
        ->and($array['amount'])->toBe(100.50)
        ->and($array['token'])->toBe('TOKEN123');
});

it('handles int and float amount types', function (): void {
    $paymentInt = new PaymentRequest(
        posID: 'POS123',
        merchantRef: 'REF456',
        merchantSession: 'SESSION789',
        amount: 100,
        currency: '132',
        is3DSec: '1',
        urlMerchantResponse: 'https://example.com/callback',
        languageMessages: 'en',
        timeStamp: '20231204120000',
        fingerprintversion: '1',
        transactionCode: '1',
        fingerprint: 'abc123',
    );

    $paymentFloat = new PaymentRequest(
        posID: 'POS123',
        merchantRef: 'REF456',
        merchantSession: 'SESSION789',
        amount: 100.50,
        currency: '132',
        is3DSec: '1',
        urlMerchantResponse: 'https://example.com/callback',
        languageMessages: 'en',
        timeStamp: '20231204120000',
        fingerprintversion: '1',
        transactionCode: '1',
        fingerprint: 'abc123',
    );

    expect($paymentInt->amount)->toBe(100)
        ->and($paymentFloat->amount)->toBe(100.50);
});

it('is readonly and immutable', function (): void {
    $payment = new PaymentRequest(
        posID: 'POS123',
        merchantRef: 'REF456',
        merchantSession: 'SESSION789',
        amount: 100.50,
        currency: '132',
        is3DSec: '1',
        urlMerchantResponse: 'https://example.com/callback',
        languageMessages: 'en',
        timeStamp: '20231204120000',
        fingerprintversion: '1',
        transactionCode: '1',
        fingerprint: 'abc123',
    );

    expect($payment->posID)->toBe('POS123')
        ->and($payment->amount)->toBe(100.50);
});
