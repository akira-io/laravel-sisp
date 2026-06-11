<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback\Pipes;

use Akira\Sisp\Actions\Transaction\FailTransactionAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\CallbackPipe;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Akira\Sisp\Support\SispAmount;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Closure;

final readonly class EnsureCallbackMatchesTransaction implements CallbackPipe
{
    public function __construct(
        private SispCredentialsResolver $credentialsResolver,
        private LoadConfig $config,
        private FailTransactionAction $failTransaction,
    ) {}

    public function handle(CallbackContext $context, Closure $next): CallbackContext
    {
        if (! $this->matchesTransaction($context->transaction(), $context->payload)) {
            $this->failTransaction->handle($context->transaction(), $context->payload, 'callback_details_mismatch');

            event(new PaymentFailed($context->transaction(), $context->payload));

            return $context->fail('callback_details_mismatch');
        }

        return $next($context);
    }

    private function matchesTransaction(Transaction $transaction, CallbackPayload $payload): bool
    {
        return $this->transactionString($transaction, 'merchant_ref') === $payload->merchantRef
            && $this->transactionString($transaction, 'merchant_session') === $payload->merchantSession
            && $this->amountMatches($this->transactionAmount($transaction), $payload->amount)
            && (! $payload->currencyProvided || $this->transactionString($transaction, 'currency') === $payload->currency)
            && (! $payload->transactionCodeProvided || $this->transactionCode($transaction) === $payload->transactionCode)
            && (! $payload->posIDProvided || $this->credentialsResolver->resolve()->posId === $payload->posID);
    }

    private function amountMatches(float|int|string $expected, float|int|string $actual): bool
    {
        return SispAmount::toThousandths($expected) === SispAmount::toThousandths($actual);
    }

    private function transactionAmount(Transaction $transaction): float|int|string
    {
        $amount = $transaction->getAttribute('amount');

        return is_float($amount) || is_int($amount) || is_string($amount) ? $amount : 0;
    }

    private function transactionString(Transaction $transaction, string $attribute): string
    {
        $value = $transaction->getAttribute($attribute);

        return is_string($value) ? $value : '';
    }

    private function transactionCode(Transaction $transaction): string
    {
        $transactionCode = $this->transactionString($transaction, 'transaction_code');

        return $transactionCode === '' ? $this->config->getDefaultTransactionCode() : $transactionCode;
    }
}
