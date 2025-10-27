<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transactions;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Transaction;

final class StoreTransactionAction
{
    /**
     * Store the transaction in the database.
     *
     * @param  array<string, mixed>  $details
     */
    public function handle(string|int $transactionId, float $amount, array $details): void
    {
       
        Transaction::create([
            'transactionId' => $transactionId,
            'merchantRespMerchantRef' => Sisp::getMerchantSession(),
            'merchantRespMerchantSession' => Sisp::getMerchantReference(),
            'merchantRespPurchaseAmount' => $amount,
            'details' => $details,
        ]);
    }
}
