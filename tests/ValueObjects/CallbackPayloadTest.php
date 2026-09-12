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

it('captures the error fields of a refused callback', function (): void {
    $payload = CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => 'R1',
        'merchantRespMerchantSession' => 'S1',
        'merchantRespMessageID' => 'MSG-1',
        'merchantRespTimeStamp' => '2026-09-12 10:00:00',
        'merchantRespErrorCode' => '3',
        'merchantRespErrorDescription' => 'Saldo insuficiente',
        'merchantRespErrorDetail' => 'Transaction Refusal Balance',
        'merchantRespAdditionalErrorMessage' => 'Saldo do cartao insuficiente',
        'merchantRespScreenError' => 'Pagamento recusado',
        'resultFingerPrint' => 'FP',
        'resultFingerPrintVersion' => '1',
        'languageMessages' => 'pt',
    ]);

    expect($payload->errorCode)->toBe('3')
        ->and($payload->errorDescription)->toBe('Saldo insuficiente')
        ->and($payload->errorDetail)->toBe('Transaction Refusal Balance')
        ->and($payload->screenError)->toBe('Pagamento recusado')
        ->and($payload->fingerprintVersion)->toBe('1')
        ->and($payload->languageMessages)->toBe('pt')
        ->and($payload->isError())->toBeTrue();
});

it('keeps the raw post out of the normalised array', function (): void {
    $payload = CallbackPayload::from([
        'messageType' => '8',
        'merchantRespMerchantRef' => 'R1',
        'merchantRespMerchantSession' => 'S1',
        'unknownFieldFromSisp' => 'kept',
    ]);

    expect($payload->raw)->toHaveKey('unknownFieldFromSisp')
        ->and($payload->toArray())->not->toHaveKey('unknownFieldFromSisp')
        ->and($payload->toArray())->not->toHaveKey('raw');
});

it('reads the cancellation post in both spellings and without the prefix', function (string $key): void {
    $payload = CallbackPayload::from([
        $key => 'true',
        'merchantRef' => 'R9',
        'merchantSession' => 'S9',
    ]);

    expect($payload->userCancelled)->toBeTrue()
        ->and($payload->merchantRef)->toBe('R9')
        ->and($payload->merchantSession)->toBe('S9');
})->with(['userCancelled', 'UserCancelled']);

it('reads the reload code under the documented name', function (): void {
    $payload = CallbackPayload::from([
        'merchantRespMerchantRef' => 'R1',
        'merchantRespMerchantSession' => 'S1',
        'merchantRespReloadCode' => '000000000123',
    ]);

    expect($payload->reloadCode)->toBe('000000000123');
});

it('falls back to the legacy reload code key', function (): void {
    $payload = CallbackPayload::from([
        'merchantRespMerchantRef' => 'R1',
        'merchantRespMerchantSession' => 'S1',
        'reloadCode' => '000000000456',
    ]);

    expect($payload->reloadCode)->toBe('000000000456');
});

it('is not an error for every success message type', function (string $messageType): void {
    $payload = CallbackPayload::from([
        'messageType' => $messageType,
        'merchantRespMerchantRef' => 'R1',
        'merchantRespMerchantSession' => 'S1',
    ]);

    expect($payload->isError())->toBeFalse();
})->with(['8', 'A', 'B', 'C', 'P', 'M', '10', '?']);
