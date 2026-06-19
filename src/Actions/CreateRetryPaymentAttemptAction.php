<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
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
            try {
                return DB::transaction(function () use ($transaction): PaymentRequest {
                    /** @var Transaction $lockedTransaction */
                    $lockedTransaction = Transaction::query()
                        ->lockForUpdate()
                        ->findOrFail($transaction->id);

                    $paymentRequest = $this->retryPayment->handle($lockedTransaction, rotateMerchantSession: false);
                    $attemptSession = $this->nextLocalAttemptSession($lockedTransaction);

                    TransactionLogContext::run(
                        'retry',
                        fn (): \Akira\Sisp\Models\TransactionAttempt => $this->createAttempt->handle(
                            $lockedTransaction,
                            $paymentRequest,
                            supersedeCurrent: true,
                            attemptSession: $attemptSession,
                        )
                    );

                    return $paymentRequest;
                }, attempts: 3);
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

    private function nextLocalAttemptSession(Transaction $transaction): string
    {
        $base = $this->config->getMerchantSession();
        $candidate = $base;
        $counter = 1;

        while ($transaction->attempts()->where('attempt_session', $candidate)->exists()) {
            $candidate = $this->suffixedAttemptSession($base, $transaction, $counter);
            $counter++;
        }

        return $candidate;
    }

    private function suffixedAttemptSession(string $base, Transaction $transaction, int $counter): string
    {
        $suffix = '-try-'.$transaction->id.'-'.$counter;

        return mb_substr($base, 0, 255 - mb_strlen($suffix)).$suffix;
    }

    private function waitForNextCandidate(): void
    {
        $microseconds = $this->config->getIdentifierGenerationCollisionRetrySleepMicroseconds();

        if ($microseconds > 0) {
            \Illuminate\Support\Sleep::usleep($microseconds);
        }
    }
}
