<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback\Pipes;

use Akira\Sisp\Contracts\CallbackPipe;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Closure;

final readonly class DispatchPaymentEvents implements CallbackPipe
{
    public function handle(CallbackContext $context, Closure $next): CallbackContext
    {
        if (! $context->transactionStatusPropagated) {
            return $next($context);
        }

        $transaction = $context->transaction();

        match ($transaction->status) {
            TransactionStatus::completed => event(new PaymentCompleted($transaction, $context->payload)),
            TransactionStatus::failed => event(new PaymentFailed($transaction, $context->payload)),
            TransactionStatus::pending => event(new PaymentPending($transaction, $context->payload)),
            default => null, // @codeCoverageIgnore
        };

        return $next($context);
    }
}
