<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentFormAction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('renders inertia with locale applied', function (): void {
    // Build a minimal payment request via container
    $data = PaymentRequestData::from([
        'amount' => 10.0,
        'merchantRef' => 'ref',
        'merchantSession' => 'sess',
        'currency' => '132',
        'customer_email' => 'a@b.test',
    ]);

    $req = resolve(\Akira\Sisp\Facades\Sisp::getFacadeRoot()::class)->buildRequestPayload($data);

    $resp = resolve(RenderPaymentFormAction::class)->renderInertia($req, 'Sisp/PaymentForm', 'pt');
    expect($resp)->toBeInstanceOf(Inertia\Response::class);
});

