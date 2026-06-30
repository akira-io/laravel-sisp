<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Concerns;

use Akira\Sisp\Models\Transaction;
use Carbon\CarbonInterface;

trait ResolvesTransaction
{
    private function resolveTransaction(string $identifier): ?Transaction
    {
        $query = Transaction::query();

        if (ctype_digit($identifier)) {
            return $query->where('id', (int) $identifier)
                ->orWhere('merchant_ref', $identifier)
                ->first();
        }

        return $query->where('merchant_ref', $identifier)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionSummary(Transaction $transaction): array
    {
        $createdAt = $transaction->getAttributeValue('created_at');

        return [
            'id' => $transaction->id,
            'merchant_ref' => $transaction->merchant_ref,
            'merchant_session' => $transaction->merchant_session,
            'status' => $transaction->status->value,
            'amount' => $transaction->amount,
            'amount_cents' => $transaction->amount_cents,
            'transaction_id' => $transaction->transaction_id,
            'message_type' => $transaction->message_type,
            'response_code' => $transaction->response_code,
            'customer_email' => $transaction->customer_email,
            'locale' => $transaction->locale,
            'created_at' => $createdAt instanceof CarbonInterface ? $createdAt->toIso8601String() : null,
        ];
    }
}
