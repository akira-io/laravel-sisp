<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback\Pipes;

use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Contracts\CallbackPipe;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Closure;

final readonly class ApplyTransactionStatus implements CallbackPipe
{
    public function __construct(private UpdateTransactionAction $updateTransaction) {}

    public function handle(CallbackContext $context, Closure $next): CallbackContext
    {
        $context->transactionStatusPropagated = $this->updateTransaction->handle(
            $context->transaction(),
            $context->payload,
            $context->attempt(),
        );

        if ($context->transactionStatusPropagated) {
            $context->transaction()->refresh();
        }

        return $next($context);
    }
}
