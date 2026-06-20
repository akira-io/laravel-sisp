<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\Actions\CreateIdempotentPaymentTransactionAction;
use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Exceptions\PaymentIntentAlreadyProcessingException;
use Akira\Sisp\Http\Requests\StorePaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class PaymentController
{
    public function __construct(
        private CreateIdempotentPaymentTransactionAction $createPayment,
        private PreparePaymentAction $preparePayment,
        private CreateAndStorePaymentTransactionAction $createTransaction,
        private RenderPaymentFormBasedOnConfigAction $renderForm,
        private CheckRateLimitAction $checkRateLimit,
        private CheckBlacklistAction $checkBlacklist,
        private StoreRequestMetadataAction $storeMetadata,
        private LoadConfig $config,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(StorePaymentRequest $request): mixed
    {
        $ip = $request->ip();

        $this->checkBlacklist->handle('ip', $ip);

        $this->checkRateLimit->handle(
            identifier: $ip,
        );

        $requestData = PaymentRequestData::from($request->validated());

        if ($this->config->isIdempotencyEnabled()) {
            try {
                $preparedPayment = $this->createPayment->handle($requestData, $request);
            } catch (PaymentIntentAlreadyProcessingException) {
                return $this->paymentIntentAlreadyProcessingResponse($request);
            }

            $paymentRequest = $preparedPayment->paymentRequest;
            $transaction = $preparedPayment->transaction;
        } else {
            $paymentRequest = $this->preparePayment->handle($requestData);
            $transaction = $this->createTransaction->handle($paymentRequest, $request, recordAttempt: false);
        }

        if ($this->config->isMetadataCollectionEnabled()) {
            $this->storeMetadata->handle($request, $transaction);
        }

        return $this->renderForm->handle($paymentRequest, $transaction->locale);
    }

    private function paymentIntentAlreadyProcessingResponse(StorePaymentRequest $request): Response
    {
        $message = __('sisp::messages.validation.payment_in_progress');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        return back(303)
            ->withErrors(['payment' => $message]);
    }
}
