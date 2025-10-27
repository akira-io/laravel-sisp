<?php

declare(strict_types=1);

namespace Akira\Sisp\Events;

use Akira\Sisp\Transaction;
use Illuminate\Foundation\Events\Dispatchable;

final class TransactionCancelled
{
    use Dispatchable;

    public function __construct(
        public Transaction $transaction,
        public string $reason = 'user_cancelled',
    ) {}
}