<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class FailTransactionAction
{
    public function handle(Transaction $transaction, CallbackPayload $payload, string $merchantResponse): void
    {
        TransactionLogContext::run(
            'callback',
            fn (): bool => $transaction->update([
                'transaction_id' => $payload->transactionID,
                'message_type' => $payload->messageType,
                'merchant_response' => $merchantResponse,
                'response_code' => $payload->merchantRespCp,
                'fingerprint' => $payload->fingerprint,
                'status' => TransactionStatus::failed,
            ])
        );
    }
}
