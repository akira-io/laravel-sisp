<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
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
        private LoadConfig $config,
    ) {}

    public function handle(PaymentRequestData $data, Request $request, bool $recordAttempt = true): PreparedPaymentTransaction
    {
        $maxAttempts = $this->maxAttempts();
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $paymentRequest = $this->preparePayment->handle($data);

            try {
                return new PreparedPaymentTransaction(
                    paymentRequest: $paymentRequest,
                    transaction: $this->createTransaction->handle($paymentRequest, $request, $recordAttempt),
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
        return $this->config->getIdentifierGenerationMaxAttempts();
    }

    private function waitForNextCandidate(): void
    {
        $microseconds = $this->config->getIdentifierGenerationCollisionRetrySleepMicroseconds();

        if ($microseconds > 0) {
            \Illuminate\Support\Sleep::usleep($microseconds);
        }
    }
}
