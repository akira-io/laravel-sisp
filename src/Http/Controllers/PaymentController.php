<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\Http\Requests\StorePaymentRequest;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Akira\Sisp\Pipelines\Payment\ProcessPaymentPipeline;
use Akira\Sisp\ValueObjects\PaymentRequestData;
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
        $context = $this->pipeline->run(new PaymentContext(
            data: PaymentRequestData::from($request->validated()),
            request: $request,
        ));

        return $this->renderForm->handle($context->paymentRequest(), $context->transaction()->locale);
    }
}
