<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\StoreRequestMetadataAction;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;

final readonly class CaptureRequestMetadata implements PaymentPipe
{
    public function __construct(private StoreRequestMetadataAction $storeMetadata) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $this->storeMetadata->handle($context->request, $context->transaction);

        return $next($context);
    }
}
