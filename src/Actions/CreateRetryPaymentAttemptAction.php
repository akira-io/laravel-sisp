<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
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
        private LoadConfig $config,
    ) {}

    public function handle(Transaction $transaction): PaymentRequest
    {
        $maxAttempts = $this->config->getIdentifierGenerationMaxAttempts();
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $paymentRequest = $this->retryPayment->handle($transaction->refresh());

            try {
                DB::transaction(function () use ($paymentRequest, $transaction): void {
                    /** @var Transaction $lockedTransaction */
                    $lockedTransaction = Transaction::query()
                        ->lockForUpdate()
                        ->findOrFail($transaction->id);

                    TransactionLogContext::run(
                        'retry',
                        function () use ($lockedTransaction, $paymentRequest): void {
                            $this->createAttempt->handle($lockedTransaction, $paymentRequest, supersedeCurrent: true);

                            $lockedTransaction->update([
                                'merchant_session' => $paymentRequest->merchantSession,
                                'transaction_id' => null,
                                'message_type' => null,
                                'merchant_response' => null,
                                'response_code' => null,
                                'fingerprint' => null,
                                'status' => TransactionStatus::pending,
                            ]);
                        }
                    );
                }, attempts: 3);

                return $paymentRequest;
            } catch (QueryException $exception) {
                throw_unless(UniqueConstraintViolation::causedBy($exception), $exception);

                $lastException = $exception;

                if ($attempt < $maxAttempts) {
                    $this->waitForNextCandidate();
                }
            }
        }

        throw new UnableToGenerateUniquePaymentIdentifiersException($maxAttempts, $lastException);
    }

    private function waitForNextCandidate(): void
    {
        $microseconds = $this->config->getIdentifierGenerationCollisionRetrySleepMicroseconds();

        if ($microseconds > 0) {
            \Illuminate\Support\Sleep::usleep($microseconds);
        }
    }
}
