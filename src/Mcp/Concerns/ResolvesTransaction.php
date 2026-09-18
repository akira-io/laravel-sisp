<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Concerns;

use Akira\Sisp\Models\Transaction;
use Carbon\CarbonInterface;
use Laravel\Mcp\Response;

trait ResolvesTransaction
{
    private function resolveTransaction(string $identifier): ?Transaction
    {
        if (ctype_digit($identifier)) {
            $transaction = Transaction::query()->where('id', (int) $identifier)->first();

            if ($transaction instanceof Transaction) {
                return $transaction;
            }
        }

        return Transaction::query()->where('merchant_ref', $identifier)->first();
    }

    private function transactionNotFound(string $identifier): Response
    {
        return Response::error('No transaction found for '.json_encode($identifier).'.');
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
            'status' => $transaction->status->value,
            'amount' => $transaction->amount,
            'amount_cents' => $transaction->amount_cents,
            'transaction_id' => $transaction->transaction_id,
            'message_type' => $transaction->message_type,
            'response_code' => $transaction->response_code,
            'error_code' => $transaction->error_code,
            'error_message' => $transaction->error_message === null ? null : mb_substr($transaction->error_message, 0, 255),
            'customer_email' => $this->maskEmail($transaction->customer_email),
            'locale' => $transaction->locale,
            'created_at' => $createdAt instanceof CarbonInterface ? $createdAt->toIso8601String() : null,
        ];
    }

    private function maskEmail(?string $email): ?string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return $email === null ? null : '***';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
