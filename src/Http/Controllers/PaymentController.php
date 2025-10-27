<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Actions\RenderPaymentFormAction;
use Akira\Sisp\Actions\StorePaymentTransactionAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Http\Requests\StorePaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;

final readonly class PaymentController
{
    public function __construct(
        private PreparePaymentAction $preparePayment,
        private StorePaymentTransactionAction $storeTransaction,
        private RenderPaymentFormAction $renderForm,
        private LoadConfig $loadConfig,
    ) {}

    public function store(StorePaymentRequest $request)
    {
        $requestData = PaymentRequestData::from($request->validated());
        $paymentRequest = $this->preparePayment->handle($requestData);

        $this->storeTransaction->handle($paymentRequest);

        if ($this->loadConfig->shouldUseInertia()) {
            return $this->renderForm->renderInertia(
                $paymentRequest,
                $this->loadConfig->getPaymentFormComponent()
            );
        }

        return $this->renderForm->renderBlade($paymentRequest);
    }
}