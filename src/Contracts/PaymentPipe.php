<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;

interface PaymentPipe
{
    /**
     * @param  Closure(PaymentContext): PaymentContext  $next
     */
    public function handle(PaymentContext $context, Closure $next): PaymentContext;
}
