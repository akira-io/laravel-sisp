<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Exceptions\PaymentIntentAlreadyProcessingException;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\PreparedPaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateIdempotentPaymentTransactionAction
{
    public function __construct(
        private CreateUniquePaymentTransactionAction $createPayment,
        private CreateRetryPaymentAttemptAction $createRetryAttempt,
        private CanRetryPaymentAction $canRetryPayment,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PaymentRequestData $data, Request $request): PreparedPaymentTransaction
    {
        $paymentIntentKey = $this->paymentIntentKey($request);

        if ($paymentIntentKey === null) {
            return $this->createPayment->handle($data, $request);
        }

        if (! $this->reserve($paymentIntentKey)) {
            return $this->existingPayment($paymentIntentKey);
        }

        try {
            $preparedPayment = $this->createPayment->handle($data, $request);
        } catch (Throwable $throwable) {
            $this->fail($paymentIntentKey, $throwable);

            throw $throwable;
        }

        $this->submit($paymentIntentKey, $preparedPayment->transaction);

        return $preparedPayment;
    }

    private function paymentIntentKey(Request $request): ?string
    {
        if (! (bool) config('sisp.idempotency.enabled', true)) {
            return null;
        }

        $keys = config('sisp.idempotency.request_keys', ['idempotency_key', 'checkout_intent_id']);

        if (! is_array($keys)) {
            return null;
        }

        foreach ($keys as $key) {
            if (! is_string($key)) {
                continue;
            }

            if ($key === '') {
                continue;
            }

            $value = $request->input($key);

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

    private function existingPayment(string $paymentIntentKey): PreparedPaymentTransaction
    {
        $intent = PaymentIntent::query()
            ->where('idempotency_key', $paymentIntentKey)
            ->first();

        throw_if(! $intent instanceof PaymentIntent || $intent->transaction_id === null, PaymentIntentAlreadyProcessingException::class, $paymentIntentKey);

        $transaction = Transaction::query()->find($intent->transaction_id);

        throw_unless($transaction instanceof Transaction, PaymentIntentAlreadyProcessingException::class, $paymentIntentKey);

        if ($this->canRetryPayment->handle($transaction)) {
            return new PreparedPaymentTransaction(
                paymentRequest: $this->createRetryAttempt->handle($transaction),
                transaction: $transaction->refresh(),
            );
        }

        return new PreparedPaymentTransaction(
            paymentRequest: $this->paymentRequestFrom($transaction, $paymentIntentKey),
            transaction: $transaction,
        );
    }

    private function paymentRequestFrom(Transaction $transaction, string $paymentIntentKey): PaymentRequest
    {
        $attempt = $transaction->currentAttempt()->first()
            ?? $transaction->attempts()->latest('attempt_number')->first();

        $payload = $attempt instanceof TransactionAttempt ? $attempt->payload : null;

        if ($payload === null || $payload === []) {
            $transactionPayload = $transaction->getAttribute('payload');
            $payload = is_array($transactionPayload) ? $transactionPayload : null;
        }

        throw_if($payload === null || $payload === [], PaymentIntentAlreadyProcessingException::class, $paymentIntentKey);

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
