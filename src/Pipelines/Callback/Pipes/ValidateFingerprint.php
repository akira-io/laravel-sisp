<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback\Pipes;

use Akira\Sisp\Contracts\CallbackFingerprintValidator;
use Akira\Sisp\Contracts\CallbackPipe;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Closure;
use Illuminate\Support\Facades\Log;

final readonly class ValidateFingerprint implements CallbackPipe
{
    public function __construct(
        private CallbackFingerprintValidator $validateFingerprint,
    ) {}

    public function handle(CallbackContext $context, Closure $next): CallbackContext
    {
        if (! $this->validateFingerprint->handle($context->payload)) {
            Log::warning('SISP callback rejected: the fingerprint does not match.', [
                'transaction_id' => $context->transaction()->getKey(),
                'merchant_ref' => $context->transaction()->merchant_ref,
            ]);

            $context->transactionStatusPropagated = false;

            return $context->fail('invalid_callback_fingerprint');
        }

        return $next($context);
    }
}
