<?php

declare(strict_types=1);

use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\ValueObjects\CallbackPayload;

beforeEach(function (): void {
    $this->action = resolve(ValidatePaymentResponseFingerprintAction::class);
});

it(/**
 * @throws Exception
 */ 'fingerprint is computed with correct field order', function (): void {
    $payloadData = [
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
        'reloadCode' => '',
    ];

    $expectedAmount = (int) ((float) 1000 * 1000);
    expect($expectedAmount)->toBe(1000000);

    $payload = CallbackPayload::from($payloadData);
    $fingerprint = resolve(PaymentResponseFingerPrintAction::class)->handle($payload);
    $payloadData['resultFingerPrint'] = $fingerprint;
    $payload = CallbackPayload::from($payloadData);

    $response = $this->action->handle($payload);

    expect($response)->toBeTrue();
});

it('fingerprint rejects altered amount', function (): void {
    $payloadData = [
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
        'reloadCode' => '',
    ];

    $payload = CallbackPayload::from($payloadData);
    $fingerprint = resolve(PaymentResponseFingerPrintAction::class)->handle($payload);
    $payloadData['resultFingerPrint'] = $fingerprint;

    $payloadData['merchantRespPurchaseAmount'] = '2000';
    $alteredPayload = CallbackPayload::from($payloadData);

    expect($this->action->handle($alteredPayload))->toBeFalse();
});

it('fingerprint rejects altered message type', function (): void {
    $payloadData = [
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
        'reloadCode' => '',
    ];

    $payload = CallbackPayload::from($payloadData);
    $fingerprint = resolve(PaymentResponseFingerPrintAction::class)->handle($payload);
    $payloadData['resultFingerPrint'] = $fingerprint;

    $payloadData['messageType'] = 'E';
    $alteredPayload = CallbackPayload::from($payloadData);

    expect($this->action->handle($alteredPayload))->toBeFalse();
});

it('reload code is part of fingerprint', function (): void {
    $payloadData = [
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
        'reloadCode' => '',
    ];

    $payload = CallbackPayload::from($payloadData);
    $fingerprint = resolve(PaymentResponseFingerPrintAction::class)->handle($payload);
    $payloadData['resultFingerPrint'] = $fingerprint;

    $payloadData['reloadCode'] = 'RELOAD-123';
    $alteredPayload = CallbackPayload::from($payloadData);

    expect($this->action->handle($alteredPayload))->toBeFalse();
});
