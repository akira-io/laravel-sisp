<?php

declare(strict_types=1);

use Akira\Sisp\Actions\Transaction\MaskCallbackRawPayloadAction;

it('masks all but the last four digits of a string PAN', function (): void {
    $masked = resolve(MaskCallbackRawPayloadAction::class)->handle([
        'merchantRespPan' => '4111111111111111',
    ]);

    expect($masked['merchantRespPan'])->toBe('************1111');
});

it('drops the PAN key rather than storing an array value unmasked', function (): void {
    $masked = resolve(MaskCallbackRawPayloadAction::class)->handle([
        'merchantRespPan' => ['4111111111111111', '5111111111111111'],
        'messageType' => '8',
    ]);

    expect($masked)->not->toHaveKey('merchantRespPan')
        ->and($masked['messageType'])->toBe('8');
});

it('drops the PAN key when it is an empty string', function (): void {
    $masked = resolve(MaskCallbackRawPayloadAction::class)->handle([
        'merchantRespPan' => '',
    ]);

    expect($masked)->not->toHaveKey('merchantRespPan');
});

it('leaves the payload unchanged when no PAN key is present', function (): void {
    $masked = resolve(MaskCallbackRawPayloadAction::class)->handle([
        'messageType' => '8',
    ]);

    expect($masked)->toBe(['messageType' => '8']);
});
