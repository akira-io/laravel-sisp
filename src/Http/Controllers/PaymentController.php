<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Http\Requests\StorePaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Throwable;

final readonly class PaymentController
{
    public function __construct(
        private PreparePaymentAction $preparePayment,
        private CreateAndStorePaymentTransactionAction $createTransaction,
        private RenderPaymentFormBasedOnConfigAction $renderForm,
        private CheckRateLimitAction $checkRateLimit,
        private CheckBlacklistAction $checkBlacklist,
        private StoreRequestMetadataAction $storeMetadata,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(StorePaymentRequest $request)
    {
        $ip = $request->ip();

        $this->checkBlacklist->handle('ip', $ip);

        $this->checkRateLimit->handle(
            identifier: $ip,
            limitType: 'ip',
        );

        $requestData = PaymentRequestData::from($request->validated());

        $paymentRequest = $this->preparePayment->handle($requestData);

        $transaction = $this->createTransaction->handle($paymentRequest, $request);

        $this->storeMetadata->handle($request, $transaction);

        return $this->renderForm->handle($paymentRequest);
    }
}
