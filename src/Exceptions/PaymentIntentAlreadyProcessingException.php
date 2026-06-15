<?php

declare(strict_types=1);

namespace Akira\Sisp\Exceptions;

use RuntimeException;

final class PaymentIntentAlreadyProcessingException extends RuntimeException
{
    public function __construct(public readonly string $paymentIntentKey)
    {
        parent::__construct("Payment intent [{$paymentIntentKey}] is already being processed.");
    }
}
