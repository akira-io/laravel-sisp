<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\Support\TransactionRowLock;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class LockCallbackRowsAction
{
    public function handle(Transaction $transaction, ?TransactionAttempt $attempt): bool
    {
        $locked = TransactionRowLock::acquire($transaction);

        if (! $locked instanceof Transaction) {
            return false;
        }

        TransactionRowLock::adopt($transaction, $locked);

        if (! $attempt instanceof TransactionAttempt) {
            return true;
        }

        $lockedAttempt = $attempt->newQuery()->whereKey($attempt->getKey())->lockForUpdate()->first();

        if ($lockedAttempt instanceof TransactionAttempt) {
            $attempt->setRawAttributes($lockedAttempt->getAttributes(), true);
        }

        return true;
    }

    public function alreadyRecorded(?TransactionAttempt $attempt, CallbackPayload $payload): bool
    {
        return $attempt instanceof TransactionAttempt
            && $payload->fingerprint !== ''
            && $attempt->callback_received_at !== null
            && $attempt->fingerprint === $payload->fingerprint;
    }

    public function isSettled(Transaction $transaction): bool
    {
        return in_array($transaction->status, [TransactionStatus::completed, TransactionStatus::refunded], true);
    }
}
