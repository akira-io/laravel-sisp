<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transactions;

use Akira\Sisp\Exceptions\TransactionNotFoundException;
use Akira\Sisp\Transaction;
use Illuminate\Http\Request;

final class UpdateTransactionAction
{
    /**
     * Update the transaction, and return it
     *
     * @throws TransactionNotFoundException
     */
    public function handle(Request $request): ?Transaction
    {
        $transaction = Transaction::where($this->merchantFields($request))->first();

        if (! $transaction) {
            throw new TransactionNotFoundException();
        }

        $transaction->update($request->all());

        return $transaction->fresh();
    }

    /**
     * Get the merchant fields from the request
     *
     * @return array<string, mixed>
     */
    private function merchantFields(Request $request): array
    {

        return [
            'merchantRespMerchantRef' => $request->get('merchantRespMerchantRef'),
            'merchantRespMerchantSession' => $request->get('merchantRespMerchantSession'),
        ];
    }
}
