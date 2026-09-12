<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class UpdateTransactionAttemptAction
{
    public function __construct(
        private MaskCallbackRawPayloadAction $maskCallbackRawPayload,
    ) {}

    public function handle(
        TransactionAttempt $attempt,
        CallbackPayload $payload,
        TransactionStatus $status,
        ?string $failureReason = null,
        bool $trustPayload = true,
    ): bool {
        return $attempt->update([
            'status' => $status,
            'gateway_transaction_id' => $payload->transactionID,
            'message_type' => $payload->messageType,
            'response_code' => $payload->merchantRespCp,
            'merchant_response' => $failureReason ?? $payload->merchantResponse,
            'fingerprint' => $payload->fingerprint,
            'callback_payload' => $trustPayload ? $this->maskCallbackRawPayload->handle($payload->toArray()) : null,
            'failure_reason' => $failureReason,
            'callback_received_at' => now(),
        ]);
    }
}
