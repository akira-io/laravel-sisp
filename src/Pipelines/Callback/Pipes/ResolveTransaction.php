<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback\Pipes;

use Akira\Sisp\Actions\Transaction\FindTransactionAttemptAction;
use Akira\Sisp\Contracts\CallbackPipe;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Closure;

final readonly class ResolveTransaction implements CallbackPipe
{
    public function __construct(private FindTransactionAttemptAction $findAttempt) {}

    public function handle(CallbackContext $context, Closure $next): CallbackContext
    {
        $context->attempt = $this->findAttempt->handle($context->payload);
        $context->transaction = $context->attempt->transaction;

        return $next($context);
    }
}
