<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\DB;

final readonly class FailTransactionAction
{
    public function __construct(
        private UpdateTransactionAttemptAction $updateAttempt,
        private ShouldPropagateAttemptCallbackAction $shouldPropagateAttemptCallback,
        private ResolveCustomerErrorMessageAction $resolveCustomerErrorMessage,
        private MaskCallbackRawPayloadAction $maskCallbackRawPayload,
    ) {}

    public function handle(
        Transaction $transaction,
        CallbackPayload $payload,
        string $merchantResponse,
        ?TransactionAttempt $attempt = null,
        bool $trustPayload = true,
    ): bool {
        return DB::transaction(function () use ($attempt, $merchantResponse, $payload, $transaction, $trustPayload): bool {
            if ($attempt instanceof TransactionAttempt) {
                $this->updateAttempt->handle($attempt, $payload, TransactionStatus::failed, $merchantResponse, $trustPayload);

                if (! $this->shouldPropagateAttemptCallback->handle($attempt, TransactionStatus::failed)) {
                    return false;
                }
            }

            $merchantRef = $attempt instanceof TransactionAttempt ? $attempt->merchant_ref : $transaction->merchant_ref;
            $merchantSession = $attempt instanceof TransactionAttempt ? $attempt->merchant_session : $transaction->merchant_session;

            TransactionLogContext::run(
                'callback',
                fn (): bool => $transaction->update([
                    'merchant_ref' => $merchantRef,
                    'merchant_session' => $merchantSession,
                    'transaction_id' => $payload->transactionID,
                    'message_type' => $payload->messageType,
                    'merchant_response' => $merchantResponse,
                    'response_code' => $payload->merchantRespCp,
                    'fingerprint' => $payload->fingerprint,
                    'status' => TransactionStatus::failed,
                    'error_code' => $trustPayload && $payload->errorCode !== '' ? $payload->errorCode : null,
                    'error_message' => $trustPayload ? $this->resolveCustomerErrorMessage->handle($payload) : null,
                    'callback_raw_payload' => $trustPayload ? $this->maskCallbackRawPayload->handle($payload->raw) : null,
                ])
            );

            return true;
        });
    }
}
