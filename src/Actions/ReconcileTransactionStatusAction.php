<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\LegacyPayload;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\Support\TransactionRowLock;
use Akira\Sisp\ValueObjects\CallbackPayload;
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

        try {
            $this->updateInvoiceWhenStatusStillHolds($transaction, $status);
        } catch (Throwable $exception) {
            Log::error('SISP reconciliation settled a payment but could not update its invoice.', [
                'transaction_id' => $transaction->getKey(),
                'status' => $status->value,
                'error' => $exception->getMessage(),
            ]);
        }

        $this->dispatchEvent($transaction, $status, $response);

        return $transaction->refresh();
    }

    private function updateInvoiceWhenStatusStillHolds(Transaction $transaction, TransactionStatus $status): void
    {
        $current = $transaction->newQuery()->whereKey($transaction->getKey())->first();

        if (! $current instanceof Transaction || $current->status !== $status) {
            Log::info('SISP reconciliation left an invoice alone: the transaction moved on before the invoice was updated.', [
                'transaction_id' => $transaction->getKey(),
                'reconciled_status' => $status->value,
                'current_status' => $current?->status->value,
            ]);

            return;
        }

        $this->updateInvoiceStatus->handle($transaction, $status);
    }

    private function dispatchEvent(Transaction $transaction, TransactionStatus $status, TransactionStatusResponse $response): void
    {
        $payload = $this->payloadFor($transaction, $response);

        match ($status) {
            TransactionStatus::completed => event(new PaymentCompleted($transaction, $payload)),
            TransactionStatus::failed => event(new PaymentFailed($transaction, $payload)),
            default => null,
        };
    }

    private function payloadFor(Transaction $transaction, TransactionStatusResponse $response): CallbackPayload
    {
        return new CallbackPayload(
            merchantRef: (string) $transaction->getAttribute('merchant_ref'),
            merchantSession: (string) ($transaction->getAttribute('merchant_session') ?? ''),
            timeStamp: '',
            amount: $transaction->amount,
            currency: (string) ($transaction->getAttribute('currency') ?? ''),
            transactionCode: (string) ($transaction->getAttribute('transaction_code') ?? ''),
            transactionID: (string) ($transaction->getAttribute('transaction_id') ?? ''),
            messageType: '',
            merchantResponse: $response->transactionStatusDescription ?: $response->message,
            responseCode: '',
            fingerprint: '',
            posID: $transaction->posId() ?? '',
            currencyProvided: false,
            transactionCodeProvided: false,
            posIDProvided: false,
            amountProvided: false,
            raw: $response->raw,
        );
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
