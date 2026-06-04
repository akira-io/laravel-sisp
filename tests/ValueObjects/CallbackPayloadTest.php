<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\CallbackPayload;

it('creates callback payload from array and converts back', function (): void {
    $data = [
        'messageType' => '8',
        'merchantRespCP' => '01',
        'merchantRespTid' => 'T123',
        'merchantRespMerchantRef' => 'R1',
        'merchantRespMerchantSession' => 'S1',
        'merchantRespPurchaseAmount' => 99.99,
        'merchantRespMessageID' => 'MSG-1',
        'merchantRespPan' => '****-****-****-1234',
        'merchantResp' => '00',
        'merchantRespTimeStamp' => '2024-01-01 00:00:00',
        'merchantRespReferenceNumber' => 'REF-1',
        'merchantRespEntityCode' => '10010',
        'merchantRespClientReceipt' => 'REC-1',
        'merchantRespAdditionalErrorMessage' => '',
        'reloadCode' => '',
        'resultFingerPrint' => 'FP',
        'posID' => 'POS-1',
        'currency' => '132',
        'transactionCode' => '1',
    ];

    $vo = CallbackPayload::from($data);

    expect($vo->merchantRef)->toBe('R1')
        ->and($vo->transactionID)->toBe('T123')
        ->and($vo->fingerprint)->toBe('FP');

    $roundTrip = $vo->toArray();
    expect($roundTrip['merchantRespMerchantRef'])->toBe('R1')
        ->and($roundTrip['merchantRespTid'])->toBe('T123')
        ->and($roundTrip['resultFingerPrint'])->toBe('FP');
});

it('withoutFingerprint removes fingerprint key from array', function (): void {
    $vo = CallbackPayload::from([
        'merchantRespMerchantRef' => 'R2',
        'merchantRespMerchantSession' => 'S2',
        'merchantRespTimeStamp' => 'ts',
        'merchantRespPurchaseAmount' => 10.0,
        'currency' => '132',
        'transactionCode' => '1',
        'merchantRespTid' => 'T2',
        'messageType' => '8',
        'merchantResp' => '00',
        'merchantRespCP' => '01',
        'resultFingerPrint' => 'FP2',
        'posID' => 'POS-2',
    ]);

    $arr = $vo->withoutFingerprint();
    expect($arr)->not->toHaveKey('resultFingerPrint')
        ->and($arr['merchantRespMerchantRef'])->toBe('R2')
        ->and($arr['merchantRespTid'])->toBe('T2');
});

it('tracks whether optional unsigned fields were provided', function (): void {
    $missing = CallbackPayload::from([
        'merchantRespMerchantRef' => 'R3',
        'merchantRespMerchantSession' => 'S3',
    ]);

    $empty = CallbackPayload::from([
        'merchantRespMerchantRef' => 'R4',
        'merchantRespMerchantSession' => 'S4',
        'currency' => '',
        'transactionCode' => '',
        'posID' => '',
    ]);

    expect($missing->currencyProvided)->toBeFalse()
        ->and($missing->transactionCodeProvided)->toBeFalse()
        ->and($missing->posIDProvided)->toBeFalse()
        ->and($empty->currencyProvided)->toBeTrue()
        ->and($empty->transactionCodeProvided)->toBeTrue()
        ->and($empty->posIDProvided)->toBeTrue();
});
