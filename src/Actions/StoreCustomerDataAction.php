<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\ValueObjects\CustomerData;

final readonly class StoreCustomerDataAction
{
    public function handle(Transaction $transaction, CustomerData $customerData): Transaction
    {
        TransactionLogContext::run(
            'customer-data',
            fn (): bool => $transaction->update($customerData->toArray())
        );

        return $transaction;
    }
}
