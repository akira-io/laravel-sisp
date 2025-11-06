<?php

declare(strict_types=1);

namespace Akira\Sisp\Events;

use Akira\Sisp\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;

final class TransactionRefunded
{
    use Dispatchable;

    public function __construct(
        public Transaction $transaction,
        public float $refundAmount,
        public string $reason = 'user_refund',
    ) {}
}
