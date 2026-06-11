<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback\Pipes;

use Akira\Sisp\Actions\Transaction\FailTransactionAction;
use Akira\Sisp\Contracts\CallbackFingerprintValidator;
use Akira\Sisp\Contracts\CallbackPipe;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Closure;

final readonly class ValidateFingerprint implements CallbackPipe
{
    public function __construct(
        private CallbackFingerprintValidator $validateFingerprint,
        private FailTransactionAction $failTransaction,
    ) {}

    public function handle(CallbackContext $context, Closure $next): CallbackContext
    {
        if (! $this->validateFingerprint->handle($context->payload)) {
            $this->failTransaction->handle($context->transaction(), $context->payload, 'invalid_callback_fingerprint');

            event(new PaymentFailed($context->transaction(), $context->payload));

            return $context->fail('invalid_callback_fingerprint');
        }

        return $next($context);
    }
}
