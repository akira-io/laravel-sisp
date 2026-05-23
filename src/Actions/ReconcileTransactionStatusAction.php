<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
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

        if (! $response->result) {
            Log::warning('SISP transaction status reconciliation returned an unsuccessful result.', [
                'transaction_id' => $transaction->getKey(),
                'merchant_ref' => $transaction->getAttribute('merchant_ref'),
                'message' => $response->msg,
            ]);

            return $transaction;
        }

        $status = $response->transactionStatus();
        $payload = $transaction->getAttribute('payload');
        $payload = is_array($payload) ? $payload : [];
        $payload['transaction_status_response'] = $response->raw;

        $transaction->update([
            'status' => $status->value,
            'message_type' => $response->transactionSuccess ? 'transaction_status_success' : 'transaction_status_failed',
            'merchant_response' => $response->transactionStatusDescription ?: $response->msg,
            'payload' => $payload,
        ]);

        $this->updateInvoiceStatus->handle($transaction, $status);

        return $transaction->refresh();
    }
}
