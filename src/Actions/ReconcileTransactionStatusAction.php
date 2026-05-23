<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;
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
        $payload = $transaction->getAttribute('payload');
        $payload = is_array($payload) ? $payload : [];
        $payload['transaction_status_response'] = $response->raw;

        $transaction->update([
            'status' => $status->value,
            'merchant_response' => $response->transactionStatusDescription ?: $response->message,
            'payload' => $payload,
        ]);

        $this->updateInvoiceStatus->handle($transaction, $status);

        return $transaction->refresh();
    }
}
