<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\Exceptions\PaymentIntentAlreadyProcessingException;
use Akira\Sisp\Http\Requests\StorePaymentRequest;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Akira\Sisp\Pipelines\Payment\ProcessPaymentPipeline;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class PaymentController
{
    public function __construct(
        private ProcessPaymentPipeline $pipeline,
        private RenderPaymentFormBasedOnConfigAction $renderForm,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(StorePaymentRequest $request): mixed
    {
        try {
            $context = $this->pipeline->run(new PaymentContext(
                data: PaymentRequestData::from($request->validated()),
                request: $request,
            ));
        } catch (PaymentIntentAlreadyProcessingException) {
            return $this->paymentIntentAlreadyProcessingResponse($request);
        }

        return $this->renderForm->handle($context->paymentRequest(), $context->transaction()->locale);
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
