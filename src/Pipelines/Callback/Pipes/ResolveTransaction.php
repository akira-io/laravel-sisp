<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback\Pipes;

use Akira\Sisp\Actions\Transaction\FindOrCreateTransactionAction;
use Akira\Sisp\Contracts\CallbackPipe;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Closure;

final readonly class ResolveTransaction implements CallbackPipe
{
    public function __construct(private FindOrCreateTransactionAction $findOrCreateTransaction) {}

    public function handle(CallbackContext $context, Closure $next): CallbackContext
    {
        $context->transaction = $this->findOrCreateTransaction->handle($context->payload);

        return $next($context);
    }
}
