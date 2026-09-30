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
        // The status is already committed and a later reconciliation will not
        // revisit the row, so a failing invoice must not swallow the event.
        try {
            $this->updateInvoiceStatus->handle($transaction, $status);
        } catch (Throwable $exception) {
            Log::error('SISP reconciliation settled a payment but could not update its invoice.', [
                'transaction_id' => $transaction->getKey(),
                'status' => $status->value,
                'error' => $exception->getMessage(),
            ]);
        }

        // The callback path announces a settled payment through these events, so
        // the listeners that fulfil an order run for a reconciled one as well.
        // The event follows the status this call wrote, not a re-read of the
        // row: only this call settled it, so it cannot emit twice, and a
        // callback that lands after the lock is released announces its own.
        $this->dispatchEvent($transaction, $status, $response);

        return $transaction->refresh();
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

    /**
     * The status API answers with a description and a message, not with the
     * fields a callback carries; the payload names the transaction and carries
     * the answer under raw so a listener can tell the two sources apart.
     */
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
