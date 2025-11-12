<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\ValueObjects\CallbackPayload;

beforeEach(function () {
    $this->action = app(ValidatePaymentResponseFingerprintAction::class);
});

it(/**
 * @throws Exception
 */ 'fingerprint is computed with correct field order', function () {
    $payload = [
        'messageType' => 'S',
        'merchantRespCP' => '01',
        'merchantRespMerchantRef' => 'R20251112123456',
        'merchantRespMerchantSession' => 'S20251112123456',
        'merchantRespPurchaseAmount' => '1000',
        'merchantRespMessageID' => 'ABCDEF12345',
        'merchantRespPan' => '504150XXXXXX1234',
        'merchantResp' => '00',
        'merchantRespTimeStamp' => '2025-11-12 14:34:56',
        'merchantRespReferenceNumber' => '123456789',
        'merchantRespEntityCode' => '10010',
        'merchantRespClientReceipt' => '**********',
        'merchantRespAdditionalErrorMessage' => '',
        'merchantRespReloadCode' => '',
    ];

    $expectedAmount = (int) ((float) 1000 * 1000);
    expect($expectedAmount)->toBe(1000000);

    $payload = CallbackPayload::from($payload);

    $response = $this->action->handle($payload);

    expect($response)->toBeTrue();
});

it('fingerprint rejects altered amount', function () {
    $payload = [
        'messageType' => 'S',
        'merchantRespCP' => '01',
        'merchantRespMerchantRef' => 'R20251112123456',
        'merchantRespMerchantSession' => 'S20251112123456',
        'merchantRespPurchaseAmount' => '1000',
        'merchantRespMessageID' => 'ABCDEF12345',
        'merchantRespPan' => '504150XXXXXX1234',
        'merchantResp' => '00',
        'merchantRespTimeStamp' => '2025-11-12 14:34:56',
        'merchantRespReferenceNumber' => '123456789',
        'merchantRespEntityCode' => '10010',
        'merchantRespClientReceipt' => '**********',
        'merchantRespAdditionalErrorMessage' => '',
        'merchantRespReloadCode' => '',
    ];

    $fingerprint = $this->action->computeFingerprint($payload);
    $payload['merchantRespPurchaseAmount'] = '2000';

    expect($this->action->handle($payload, $fingerprint))->toBeFalse();
});

it('fingerprint rejects altered message type', function () {
    $payload = [
        'messageType' => 'S',
        'merchantRespCP' => '01',
        'merchantRespMerchantRef' => 'R20251112123456',
        'merchantRespMerchantSession' => 'S20251112123456',
        'merchantRespPurchaseAmount' => '1000',
        'merchantRespMessageID' => 'ABCDEF12345',
        'merchantRespPan' => '504150XXXXXX1234',
        'merchantResp' => '00',
        'merchantRespTimeStamp' => '2025-11-12 14:34:56',
        'merchantRespReferenceNumber' => '123456789',
        'merchantRespEntityCode' => '10010',
        'merchantRespClientReceipt' => '**********',
        'merchantRespAdditionalErrorMessage' => '',
        'merchantRespReloadCode' => '',
    ];

    $fingerprint = $this->action->computeFingerprint($payload);
    $payload['messageType'] = 'E';

    expect($this->action->handle($payload, $fingerprint))->toBeFalse();
});

it('reload code is part of fingerprint', function () {
    $payload = [
        'messageType' => 'S',
        'merchantRespCP' => '01',
        'merchantRespMerchantRef' => 'R20251112123456',
        'merchantRespMerchantSession' => 'S20251112123456',
        'merchantRespPurchaseAmount' => '1000',
        'merchantRespMessageID' => 'ABCDEF12345',
        'merchantRespPan' => '504150XXXXXX1234',
        'merchantResp' => '00',
        'merchantRespTimeStamp' => '2025-11-12 14:34:56',
        'merchantRespReferenceNumber' => '123456789',
        'merchantRespEntityCode' => '10010',
        'merchantRespClientReceipt' => '**********',
        'merchantRespAdditionalErrorMessage' => '',
        'merchantRespReloadCode' => '',
    ];

    $fingerprint = $this->action->computeFingerprint($payload);
    $payload['merchantRespReloadCode'] = 'RELOAD-123';

    expect($this->action->handle($payload, $fingerprint))->toBeFalse();
});
