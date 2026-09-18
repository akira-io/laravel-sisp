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
    public function handle(Transaction $transaction, ?TransactionAttempt $attempt, CallbackPayload $payload): bool
    {
        $locked = TransactionRowLock::acquire($transaction);

        if (! $locked instanceof Transaction) {
            return false;
        }

        TransactionRowLock::adopt($transaction, $locked);

        if ($attempt instanceof TransactionAttempt) {
            $lockedAttempt = $attempt->newQuery()->whereKey($attempt->getKey())->lockForUpdate()->first();

            if ($lockedAttempt instanceof TransactionAttempt) {
                $attempt->setRawAttributes($lockedAttempt->getAttributes(), true);
            }
        }

        if ($locked->status === TransactionStatus::completed) {
            return false;
        }

        return ! $this->alreadyRecorded($attempt, $payload);
    }

    private function alreadyRecorded(?TransactionAttempt $attempt, CallbackPayload $payload): bool
    {
        return $attempt instanceof TransactionAttempt
            && $payload->fingerprint !== ''
            && $attempt->callback_received_at !== null
            && $attempt->fingerprint === $payload->fingerprint;
    }
}
