<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Actions\CreateRetryPaymentAttemptAction;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Exceptions\PaymentIntentAlreadyProcessingException;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ApplyPaymentIntent implements PaymentPipe
{
    public function __construct(
        private CreateRetryPaymentAttemptAction $createRetryAttempt,
        private CanRetryPaymentAction $canRetryPayment,
    ) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $paymentIntentKey = $this->paymentIntentKey($context);

        if ($paymentIntentKey === null) {
            return $next($context);
        }

        if (! $this->reserve($paymentIntentKey)) {
            return $this->existingPayment($context, $paymentIntentKey);
        }

        try {
            $context = $next($context);
        } catch (Throwable $throwable) {
            $this->fail($paymentIntentKey, $throwable);

            throw $throwable;
        }

        $this->submit($paymentIntentKey, $context->transaction());

        return $context;
    }

    private function paymentIntentKey(PaymentContext $context): ?string
    {
        if (! config()->boolean('sisp.idempotency.enabled', true)) {
            return null;
        }

        $keys = config()->array('sisp.idempotency.request_keys', ['idempotency_key', 'checkout_intent_id']);

        foreach ($keys as $key) {
            if (! is_string($key)) {
                continue;
            }

            if ($key === '') {
                continue;
            }

            $value = $context->request->input($key);

            if (is_string($value) && mb_trim($value) !== '') {
                return mb_trim($value);
            }
        }

        return null;
    }

    private function reserve(string $paymentIntentKey): bool
    {
        return DB::table((new PaymentIntent)->getTable())->insertOrIgnore([
            'idempotency_key' => $paymentIntentKey,
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    private function existingPayment(PaymentContext $context, string $paymentIntentKey): PaymentContext
    {
        $intent = PaymentIntent::query()
            ->where('idempotency_key', $paymentIntentKey)
            ->first();

        throw_if(! $intent instanceof PaymentIntent || $intent->transaction_id === null, PaymentIntentAlreadyProcessingException::class, $paymentIntentKey);

        $transaction = Transaction::query()->find($intent->transaction_id);

        throw_unless($transaction instanceof Transaction, PaymentIntentAlreadyProcessingException::class, $paymentIntentKey);

        $context->transaction = $transaction;

        if ($this->canRetryPayment->handle($transaction)) {
            $context->paymentRequest = $this->createRetryAttempt->handle($transaction);
            $context->transaction = $transaction->refresh();

            return $context;
        }

        $context->paymentRequest = $this->paymentRequestFrom($transaction, $paymentIntentKey);

        return $context;
    }

    private function paymentRequestFrom(Transaction $transaction, string $paymentIntentKey): PaymentRequest
    {
        $attempt = $transaction->currentAttempt()->first()
            ?? $transaction->attempts()->latest('attempt_number')->first();

        $payload = $attempt instanceof TransactionAttempt ? $attempt->payload : null;

        if ($payload === null || $payload === []) {
            $payload = $transaction->payload;
        }

        throw_if($payload === [], PaymentIntentAlreadyProcessingException::class, $paymentIntentKey);

        return PaymentRequest::from($payload);
    }

    private function submit(string $paymentIntentKey, Transaction $transaction): void
    {
        PaymentIntent::query()
            ->where('idempotency_key', $paymentIntentKey)
            ->update([
                'transaction_id' => $transaction->id,
                'status' => 'submitted',
                'updated_at' => now(),
            ]);
    }

    private function fail(string $paymentIntentKey, Throwable $throwable): void
    {
        PaymentIntent::query()
            ->where('idempotency_key', $paymentIntentKey)
            ->update([
                'status' => 'failed',
                'failure_reason' => mb_substr($throwable->getMessage(), 0, 65535),
                'updated_at' => now(),
            ]);
    }
}
