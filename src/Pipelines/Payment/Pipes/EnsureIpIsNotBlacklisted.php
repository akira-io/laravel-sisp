<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;

final readonly class EnsureIpIsNotBlacklisted implements PaymentPipe
{
    public function __construct(private CheckBlacklistAction $checkBlacklist) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $this->checkBlacklist->handle('ip', $context->request->ip());

        return $next($context);
    }
}
