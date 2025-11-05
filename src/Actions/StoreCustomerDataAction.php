<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Transaction;
use Akira\Sisp\ValueObjects\CustomerData;

final readonly class StoreCustomerDataAction
{
    public function handle(Transaction $transaction, CustomerData $customerData): Transaction
    {
        $transaction->update($customerData->toArray());

        return $transaction;
    }
}