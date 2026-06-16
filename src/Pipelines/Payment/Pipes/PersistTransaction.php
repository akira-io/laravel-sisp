<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment\Pipes;

use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Configuration\LoadConfig;
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
        private LoadConfig $config,
    ) {}

    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        $maxAttempts = $this->config->getIdentifierGenerationMaxAttempts();

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $context->transaction = $this->createTransaction->handle($context->paymentRequest(), $context->request);
            } catch (DuplicatePaymentIdentifierException) {
                throw_if($attempt >= $maxAttempts, UnableToGenerateUniquePaymentIdentifiersException::class, $maxAttempts);

                $this->sleepBeforeRetry();
                $context->paymentRequest = $this->preparePayment->handle($context->data);

                continue;
            }

            return $next($context);
        }

        throw new UnableToGenerateUniquePaymentIdentifiersException($maxAttempts);
    }

    private function sleepBeforeRetry(): void
    {
        $sleepMicroseconds = $this->config->getIdentifierGenerationCollisionRetrySleepMicroseconds();

        if ($sleepMicroseconds > 0) {
            \Illuminate\Support\Sleep::usleep($sleepMicroseconds);
        }
    }
}
