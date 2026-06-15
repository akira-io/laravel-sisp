<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\Support\UniqueConstraintViolation;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class CreateRetryPaymentAttemptAction
{
    public function __construct(
        private RetryPaymentAction $retryPayment,
        private CreateTransactionAttemptAction $createAttempt,
    ) {}

    public function handle(Transaction $transaction): PaymentRequest
    {
        $maxAttempts = max(1, config()->integer('sisp.identifier_generation.max_attempts', 5));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $paymentRequest = $this->retryPayment->handle($transaction);

            try {
                DB::transaction(function () use ($transaction, $paymentRequest): void {
                    $lockedTransaction = Transaction::query()
                        ->lockForUpdate()
                        ->findOrFail($transaction->id);

                    $this->createAttempt->handle($lockedTransaction, $paymentRequest, supersedeCurrent: true);

                    TransactionLogContext::run(
                        'retry',
                        fn (): bool => $lockedTransaction->update([
                            'merchant_session' => $paymentRequest->merchantSession,
                            'transaction_id' => null,
                            'message_type' => null,
                            'merchant_response' => null,
                            'response_code' => null,
                            'fingerprint' => null,
                            'status' => TransactionStatus::pending,
                        ])
                    );
                }, attempts: 3);

                return $paymentRequest;
            } catch (QueryException $exception) {
                throw_unless(UniqueConstraintViolation::causedBy($exception), $exception);

                throw_if($attempt >= $maxAttempts, UnableToGenerateUniquePaymentIdentifiersException::class, $maxAttempts);

                $this->sleepBeforeRetry();
            }
        }

        throw new UnableToGenerateUniquePaymentIdentifiersException($maxAttempts);
    }

    private function sleepBeforeRetry(): void
    {
        $sleepMicroseconds = config()->integer('sisp.identifier_generation.collision_retry_sleep_microseconds', 1_000_000);

        if ($sleepMicroseconds > 0) {
            \Illuminate\Support\Sleep::usleep($sleepMicroseconds);
        }
    }
}
