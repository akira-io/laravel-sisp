<?php

declare(strict_types=1);

use Akira\Sisp\Actions\FingerPrint\PaymentErrorResponseFingerPrintAction;
use Akira\Sisp\Actions\PostAutCode;

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
