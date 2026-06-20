<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Actions\CreateRetryPaymentAttemptAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Enums\TransactionStatus;
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
        private LoadConfig $config,
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
        if (! $this->config->isIdempotencyEnabled()) {
            return null;
        }

        foreach ($this->config->getIdempotencyRequestKeys() as $key) {
            $value = $context->request->input($key);

            if (is_string($value) && mb_trim($value) !== '') {
                return mb_trim($value);
            }
        }

        return null;
    }

    private function reserve(string $paymentIntentKey): bool
    {
        $table = (new PaymentIntent)->getTable();
        $timestamp = now();

        $reclaimed = DB::table($table)
            ->where('idempotency_key', $paymentIntentKey)
            ->where('status', 'failed')
            ->whereNull('transaction_id')
            ->update([
                'status' => 'processing',
                'failure_reason' => null,
                'updated_at' => $timestamp,
            ]);

        if ($reclaimed === 1) {
            return true;
        }

        return DB::table($table)->insertOrIgnore([
            'idempotency_key' => $paymentIntentKey,
            'status' => 'processing',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
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

        $transaction->load('currentAttempt');
        $context->transaction = $transaction;

        if ($this->canRetryPayment->handle($transaction)) {
            $context->paymentRequest = $this->retryPaymentRequest($transaction);
            $context->transaction = $transaction->refresh()->load('currentAttempt');

            return $context;
        }

        throw new PaymentIntentAlreadyProcessingException($paymentIntentKey);
    }

    private function retryPaymentRequest(Transaction $transaction): PaymentRequest
    {
        $currentAttempt = $transaction->currentAttempt;

        if ($currentAttempt instanceof TransactionAttempt && $currentAttempt->status === TransactionStatus::pending) {
            $payload = $currentAttempt->payload;

            throw_if(! is_array($payload) || $payload === [], PaymentIntentAlreadyProcessingException::class);

            return PaymentRequest::from($payload);
        }

        return $this->createRetryAttempt->handle($transaction);
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
