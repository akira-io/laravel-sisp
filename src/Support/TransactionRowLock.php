<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Akira\Sisp\Models\Transaction;

final class TransactionRowLock
{
    public static function acquire(Transaction $transaction): ?Transaction
    {
        return $transaction->newQuery()->whereKey($transaction->getKey())->lockForUpdate()->first();
    }

    public static function adopt(Transaction $transaction, Transaction $locked): void
    {
        $unsaved = $transaction->getDirty();

        $transaction->setRawAttributes($locked->getAttributes(), true);
        $transaction->setRawAttributes([...$locked->getAttributes(), ...$unsaved]);
    }
}
