<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transactions;

use Akira\Sisp\Exceptions\TransactionNotFoundException;
use Akira\Sisp\Transaction;
use Akira\Sisp\ValueObjects\TransactionValueObject;

final class UpdateTransactionAction
{
    /**
     * Update the transaction, and return it
     *
     * @throws TransactionNotFoundException
     */
    public function handle(TransactionValueObject $object): ?Transaction
    {
        $transaction = Transaction::where($this->merchantFields($object))->first();

        if (! $transaction) {
            throw new TransactionNotFoundException();
        }

        $transaction->update([
            'details' => $object->getDetails(),
        ]);

        return $transaction->fresh();
    }

    /**
     * Get the merchant fields from the request
     *
     * @return array<string, mixed>
     */
    private function merchantFields(TransactionValueObject $object): array
    {

        return [
            'merchantRespMerchantRef' => $object->getMerchantRespMerchantRef(),
            'merchantRespMerchantSession' => $object->getMerchantRespMerchantSession(),
        ];
    }
}
