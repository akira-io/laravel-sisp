<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Pipelines\Callback\Pipes\ApplyTransactionStatus;
use Akira\Sisp\Pipelines\Callback\Pipes\DispatchPaymentEvents;
use Akira\Sisp\Pipelines\Callback\Pipes\EnsureCallbackMatchesTransaction;
use Akira\Sisp\Pipelines\Callback\Pipes\ResolveTransaction;
use Akira\Sisp\Pipelines\Callback\Pipes\ValidateFingerprint;
use Illuminate\Pipeline\Pipeline;

final readonly class HandleCallbackPipeline
{
    public const array DEFAULT_PIPES = [
        ResolveTransaction::class,
        ValidateFingerprint::class,
        EnsureCallbackMatchesTransaction::class,
        ApplyTransactionStatus::class,
        DispatchPaymentEvents::class,
    ];

    public function __construct(
        private Pipeline $pipeline,
        private LoadConfig $config,
    ) {}

    public function run(CallbackContext $context): CallbackContext
    {
        return $this->pipeline
            ->send($context)
            ->through($this->config->getCallbackPipes())
            ->thenReturn();
    }
}
