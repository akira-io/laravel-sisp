<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

use Akira\Sisp\Models\Transaction;

final readonly class PreparedPaymentTransaction
{
    public function __construct(
        public PaymentRequest $paymentRequest,
        public Transaction $transaction,
    ) {}
}
