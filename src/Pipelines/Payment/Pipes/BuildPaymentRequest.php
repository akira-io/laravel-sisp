<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;

final readonly class BuildPaymentRequest implements PaymentPipe
{
    public function __construct(private PreparePaymentAction $preparePayment) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $context->paymentRequest = $this->preparePayment->handle($context->data);

        return $next($context);
    }
}
