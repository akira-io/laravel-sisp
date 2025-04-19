<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transactions;

use Akira\Sisp\Transaction;

final class StoreTransactionAction
{
    /**
     * Store the transaction in the database.
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $options
     */
    public function handle(string $transactionId, array $fields, array $options = []): void
    {
        Transaction::create([
            'transactionId' => $transactionId,
            'merchantRespMerchantRef' => data_get($fields, 'merchantRef'),
            'merchantRespMerchantSession' => data_get($fields, 'merchantSession'),
            'merchantRespPurchaseAmount' => data_get($fields, 'amount'),
            'optionalParams' => $options,
        ]);
    }
}
