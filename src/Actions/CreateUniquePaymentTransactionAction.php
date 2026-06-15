<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Exceptions\DuplicatePaymentIdentifierException;
use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\PreparedPaymentTransaction;
use Illuminate\Http\Request;

final readonly class CreateUniquePaymentTransactionAction
{
    public function __construct(
        private PreparePaymentAction $preparePayment,
        private CreateAndStorePaymentTransactionAction $createTransaction,
    ) {}

    public function handle(PaymentRequestData $data, Request $request): PreparedPaymentTransaction
    {
        $maxAttempts = $this->maxAttempts();
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $paymentRequest = $this->preparePayment->handle($data);

            try {
                return new PreparedPaymentTransaction(
                    paymentRequest: $paymentRequest,
                    transaction: $this->createTransaction->handle($paymentRequest, $request),
                );
            } catch (DuplicatePaymentIdentifierException $exception) {
                $lastException = $exception;

                if ($attempt < $maxAttempts) {
                    $this->waitForNextCandidate();
                }
            }
        }

        throw new UnableToGenerateUniquePaymentIdentifiersException($maxAttempts, $lastException);
    }

    private function maxAttempts(): int
    {
        return max(1, (int) config('sisp.identifier_generation.max_attempts', 5));
    }

    private function waitForNextCandidate(): void
    {
        $microseconds = max(0, (int) config('sisp.identifier_generation.collision_retry_sleep_microseconds', 1_000_000));

        if ($microseconds > 0) {
            \Illuminate\Support\Sleep::usleep($microseconds);
        }
    }
}
