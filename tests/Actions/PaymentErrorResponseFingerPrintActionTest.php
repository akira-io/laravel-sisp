<?php

declare(strict_types=1);

use Akira\Sisp\Actions\FingerPrint\PaymentErrorResponseFingerPrintAction;
use Akira\Sisp\Actions\PostAutCode;
use Akira\Sisp\ValueObjects\CallbackPayload;

it('hashes the error response fields in the documented order', function (): void {
    $payload = sispErrorPayload();
    $posAutCode = resolve(PostAutCode::class)->handle();

    $expected = base64_encode(hash('sha512', implode('', [
        $posAutCode,
        '6',
        'MSG-1',
        '3',
        'Transaction Refusal Balance',
        'Saldo insuficiente',
        'R1',
        'S1',
        'Saldo do cartao insuficiente',
        '2026-09-12 10:00:00',
    ]), true));

    expect(resolve(PaymentErrorResponseFingerPrintAction::class)->handle($payload))
        ->toBe($expected);
});

it('produces a different hash when the error description changes', function (): void {
    $action = resolve(PaymentErrorResponseFingerPrintAction::class);

    expect($action->handle(sispErrorPayload()))
        ->not->toBe($action->handle(sispErrorPayload([
            'merchantRespErrorDescription' => 'Cartao expirado',
        ])));
});

it('produces a well formed digest for a real refused-callback vector, without asserting a hash match against its unavailable production posAutCode', function (): void {
    $payload = CallbackPayload::from([
        'messageType' => '6',
        'merchantRespMerchantRef' => 'Rmty42n92xhktp4',
        'merchantRespMerchantSession' => 'Smty42n93z2s0t0',
        'merchantRespMessageID' => 'DuEbp1l6qYNTKIbz01y1',
        'merchantRespErrorCode' => 'F',
        'merchantRespErrorDescription' => 'FALHA NA AUTENTICACAO CLIENTE',
        'merchantRespErrorDetail' => 'FALHA NA AUTENTICACAO CLIENTE',
        'languageMessages' => 'EN',
        'merchantRespAdditionalErrorMessage' => 'FALHA NA AUTENTICACAO CLIENTE',
        'merchantRespTimeStamp' => '2026-09-12 07:16:47',
        'resultFingerPrint' => 'BqbXjUjqlVNPoNNf24xQGUshTmdmzrzVh/tIhaggOfE8J1USss1+L4QCaAqolGpvxFDViWpktO0RAd+jMFfrQg==',
    ]);

    expect($payload->errorCode)->toBe('F');

    $digest = resolve(PaymentErrorResponseFingerPrintAction::class)->handle($payload);

    expect(mb_strlen(bin2hex((string) base64_decode($digest, true))))->toBe(128);
});
