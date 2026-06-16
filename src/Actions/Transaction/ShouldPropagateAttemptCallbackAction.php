<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\TransactionAttempt;

final readonly class ShouldPropagateAttemptCallbackAction
{
    public function handle(TransactionAttempt $attempt, TransactionStatus $status): bool
    {
        if ($attempt->isCurrent()) {
            return true;
        }

        return $status === TransactionStatus::completed;
    }
}
