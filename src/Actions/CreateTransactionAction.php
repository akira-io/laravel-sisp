<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionData;

final readonly class CreateTransactionAction
{
    public function handle(TransactionData $data): Transaction
    {
        return Transaction::create([
            'merchant_ref' => $data->merchantRef,
            'merchant_session' => $data->merchantSession,
            'amount' => $data->amount,
            'currency' => $data->currency,
            'status' => 'pending',
            'transaction_code' => $data->transactionCode,
            'payload' => $data->payload,
        ]);
    }
}
