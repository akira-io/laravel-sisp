<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\ValueObjects\PaymentRequest;

function samplePaymentRequest(): PaymentRequest {
    return PaymentRequest::from([
        'posID' => config('sisp.posID'),
        'merchantRef' => 'R123',
        'merchantSession' => 'S123',
        'amount' => 10.0,
        'currency' => config('sisp.currency'),
        'is3DSec' => config('sisp.is_3dsec'),
        'urlMerchantResponse' => config('sisp.url_merchant_response'),
        'languageMessages' => config('sisp.language_messages'),
        'timeStamp' => '2024-01-01 00:00:00',
        'fingerprintversion' => config('sisp.fingerprint_version'),
        'transactionCode' => config('sisp.transaction_code'),
        'fingerprint' => 'fp',
        'locale' => 'pt_PT',
    ]);
}

it('uses Inertia when configured', function (): void {
    config()->set('sisp.use_inertia.enabled', true);

    $action = resolve(RenderPaymentFormBasedOnConfigAction::class);
    $result = $action->handle(samplePaymentRequest(), 'en');

    expect($result)->toBeInstanceOf(\Inertia\Response::class);
});

it('falls back to Blade when Inertia disabled', function (): void {
    config()->set('sisp.use_inertia.enabled', false);

    $action = resolve(RenderPaymentFormBasedOnConfigAction::class);
    $result = $action->handle(samplePaymentRequest());

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
});
