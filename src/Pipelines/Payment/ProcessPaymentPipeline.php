<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Pipelines\Payment\Pipes\BuildPaymentRequest;
use Akira\Sisp\Pipelines\Payment\Pipes\CaptureRequestMetadata;
use Akira\Sisp\Pipelines\Payment\Pipes\EnforceRateLimits;
use Akira\Sisp\Pipelines\Payment\Pipes\EnsureIpIsNotBlacklisted;
use Akira\Sisp\Pipelines\Payment\Pipes\PersistTransaction;
use Illuminate\Pipeline\Pipeline;

final readonly class ProcessPaymentPipeline
{
    public const array DEFAULT_PIPES = [
        EnsureIpIsNotBlacklisted::class,
        EnforceRateLimits::class,
        BuildPaymentRequest::class,
        PersistTransaction::class,
        CaptureRequestMetadata::class,
    ];

    public function __construct(
        private Pipeline $pipeline,
        private LoadConfig $config,
    ) {}

    public function run(PaymentContext $context): PaymentContext
    {
        return $this->pipeline
            ->send($context)
            ->through($this->config->getPaymentPipes())
            ->thenReturn();
    }
}
