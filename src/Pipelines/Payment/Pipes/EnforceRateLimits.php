<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CheckRateLimitAction;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;

final readonly class EnforceRateLimits implements PaymentPipe
{
    public function __construct(private CheckRateLimitAction $checkRateLimit) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $this->checkRateLimit->handle(identifier: $context->request->ip());

        return $next($context);
    }
}
