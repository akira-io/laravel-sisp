<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CanRetryPaymentAction;
use Akira\Sisp\Actions\CreateRetryPaymentAttemptAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Exceptions\PaymentIntentAlreadyProcessingException;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class ApplyPaymentIntent implements PaymentPipe
{
    public function __construct(
        private CanRetryPaymentAction $canRetryPayment,
        private CreateRetryPaymentAttemptAction $createRetryAttempt,
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

        throw_unless($this->canRetryPayment->handle($transaction), PaymentIntentAlreadyProcessingException::class, $paymentIntentKey);

        $context->paymentRequest = $this->createRetryAttempt->handle($transaction);
        $context->transaction = $transaction->refresh()->load('currentAttempt');

        return $context;
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
