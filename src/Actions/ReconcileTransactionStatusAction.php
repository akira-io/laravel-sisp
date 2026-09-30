<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\LegacyPayload;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\Support\TransactionRowLock;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class ReconcileTransactionStatusAction
{
    public function __construct(
        private QueryTransactionStatusAction $queryTransactionStatus,
        private UpdateInvoiceStatusAction $updateInvoiceStatus,
    ) {}

    public function handle(Transaction $transaction): Transaction
    {
        if ($transaction->status !== TransactionStatus::pending) {
            return $transaction;
        }

        try {
            $response = $this->queryTransactionStatus->handle($transaction);
        } catch (Throwable $exception) {
            Log::warning('SISP transaction status reconciliation failed.', [
                'transaction_id' => $transaction->getKey(),
                'merchant_ref' => $transaction->getAttribute('merchant_ref'),
                'error' => $exception->getMessage(),
            ]);

            return $transaction;
        }

        return $this->applyResponse($transaction, $response);
    }

    public function applyResponse(Transaction $transaction, TransactionStatusResponse $response): Transaction
    {
        if ($transaction->status !== TransactionStatus::pending || ! $response->result) {
            return $transaction;
        }

        $status = $response->paymentStatus();

        // The status query can take seconds, during which the real callback may have
        // settled the row. Lock it and re-read the status before writing, so a stale
        // answer from SISP never overwrites a completed payment.
        $applied = DB::transaction(function () use ($transaction, $response, $status): bool {
            $locked = TransactionRowLock::acquire($transaction);

            if (! $locked instanceof Transaction) {
                return false;
            }

            TransactionRowLock::adopt($transaction, $locked);

            if ($transaction->status !== TransactionStatus::pending) {
                Log::info('SISP reconciliation left a transaction alone: its status changed while the gateway was being queried.', [
                    'transaction_id' => $transaction->getKey(),
                    'status' => $transaction->status->value,
                ]);

                return false;
            }

            TransactionLogContext::run(
                'reconciliation',
                fn (): bool => $transaction->update($this->changes($transaction, $response, $status))
            );

            return true;
        });

        if (! $applied) {
            return $transaction;
        }

        // Outside the transaction: a completed payment renders its invoice PDF
        // here, and that must not hold the row lock a callback may be waiting on.
        $this->updateInvoiceStatus->handle($transaction, $status);

        return $transaction->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function changes(Transaction $transaction, TransactionStatusResponse $response, TransactionStatus $status): array
    {
        $changes = [
            'status' => $status->value,
            'merchant_response' => $response->transactionStatusDescription ?: $response->message,
        ];

        $stored = $transaction->getAttribute('payload');
        $payload = $stored === null ? [] : LegacyPayload::decode($stored);

        if ($payload === null) {
            Log::warning('SISP reconciliation left an undecodable transaction payload untouched.', [
                'transaction_id' => $transaction->getKey(),
            ]);

            return $changes;
        }

        $payload['transaction_status_response'] = $response->raw;
        $changes['payload'] = $payload;

        return $changes;
    }
}
