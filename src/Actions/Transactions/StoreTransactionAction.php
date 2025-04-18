<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transactions;

use Akira\Sisp\Http\Requests\PaymentRequest;
use Akira\Sisp\Transaction;

final class StoreTransactionAction
{
    /**
     * Store the transaction in the database.
     *
     * @param  array<string, mixed>  $fields
     */
    public function handle(PaymentRequest $request, array $fields): void
    {
        Transaction::create([
            'transactionId' => $request->validated('transactionId'),
            'merchantRespMerchantRef' => data_get($fields, 'merchantRef'),
            'merchantRespMerchantSession' => data_get($fields, 'merchantSession'),
            'merchantRespPurchaseAmount' => data_get($fields, 'amount'),
            'optionalParams' => data_get($fields, 'optionalParams'),
        ]);
    }
}
