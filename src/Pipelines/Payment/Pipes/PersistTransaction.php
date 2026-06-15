<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Exceptions\DuplicatePaymentIdentifierException;
use Akira\Sisp\Exceptions\UnableToGenerateUniquePaymentIdentifiersException;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;

final readonly class PersistTransaction implements PaymentPipe
{
    public function __construct(
        private CreateAndStorePaymentTransactionAction $createTransaction,
        private PreparePaymentAction $preparePayment,
    ) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $maxAttempts = max(1, config()->integer('sisp.identifier_generation.max_attempts', 5));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $context->transaction = $this->createTransaction->handle($context->paymentRequest(), $context->request);

                return $next($context);
            } catch (DuplicatePaymentIdentifierException) {
                throw_if($attempt >= $maxAttempts, UnableToGenerateUniquePaymentIdentifiersException::class, $maxAttempts);

                $this->sleepBeforeRetry();
                $context->paymentRequest = $this->preparePayment->handle($context->data);
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
