<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\TransactionAttempt;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class UpdateTransactionAttemptAction
{
    public function handle(
        TransactionAttempt $attempt,
        CallbackPayload $payload,
        TransactionStatus $status,
        ?string $failureReason = null,
    ): bool {
        return $attempt->update([
            'status' => $status,
            'gateway_transaction_id' => $payload->transactionID,
            'message_type' => $payload->messageType,
            'merchant_response' => $failureReason ?? $payload->merchantResponse,
            'response_code' => $payload->merchantRespCp,
            'fingerprint' => $payload->fingerprint,
            'callback_payload' => $payload->toArray(),
            'failure_reason' => $failureReason,
            'callback_received_at' => now(),
        ]);
    }
}
