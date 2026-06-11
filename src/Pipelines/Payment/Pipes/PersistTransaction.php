<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;

final readonly class PersistTransaction implements PaymentPipe
{
    public function __construct(private CreateAndStorePaymentTransactionAction $createTransaction) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $context->transaction = $this->createTransaction->handle($context->paymentRequest(), $context->request);

        return $next($context);
    }
}
