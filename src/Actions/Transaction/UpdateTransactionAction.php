<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTransactionAction
{
    public function __construct(
        private MapTransactionStatusAction $mapStatus,
        private UpdateTransactionAttemptAction $updateAttempt,
        private ShouldPropagateAttemptCallbackAction $shouldPropagateAttemptCallback,
    ) {}

    public function handle(Transaction $transaction, CallbackPayload $payload, ?TransactionAttempt $attempt = null): bool
    {
        $status = $this->mapStatus->handle($payload->messageType);

        return DB::transaction(function () use ($attempt, $payload, $status, $transaction): bool {
            if ($attempt instanceof TransactionAttempt) {
                $this->updateAttempt->handle($attempt, $payload, $status);

                if (! $this->shouldPropagateAttemptCallback->handle($attempt, $status)) {
                    return false;
                }
            }

            $merchantRef = $attempt instanceof TransactionAttempt ? $attempt->merchant_ref : $transaction->merchant_ref;
            $merchantSession = $attempt instanceof TransactionAttempt ? $attempt->merchant_session : $transaction->merchant_session;

            return TransactionLogContext::run(
                'callback',
                fn (): bool => $transaction->update([
                    'merchant_ref' => $merchantRef,
                    'merchant_session' => $merchantSession,
                    'transaction_id' => $payload->transactionID,
                    'message_type' => $payload->messageType,
                    'merchant_response' => $payload->merchantResponse,
                    'response_code' => $payload->merchantRespCp,
                    'fingerprint' => $payload->fingerprint,
                    'payload' => $transaction->payload,
                    'status' => $status,
                ])
            );
        });
    }
}
