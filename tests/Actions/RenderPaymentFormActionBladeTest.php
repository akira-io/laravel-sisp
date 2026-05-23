<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentFormAction;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('renders blade payment form view', function (): void {
    config()->set('sisp.use_inertia.enabled', false);

    $data = PaymentRequestData::from([
        'amount' => 15.5,
        'merchantRef' => 'R1',
        'merchantSession' => 'S1',
        'currency' => '132',
        'transactionCode' => '1',
    ]);
    $req = Sisp::buildRequestPayload($data);

    $view = resolve(RenderPaymentFormAction::class)->renderBlade($req);
    expect($view->name())->toBe('sisp::payment-form')
        ->and($view->render())->toContain(trans('sisp::payment.manual_redirect_button'));
});
