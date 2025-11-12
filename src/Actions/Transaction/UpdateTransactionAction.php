<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class UpdateTransactionAction
{
    public function __construct(
        private MapTransactionStatusAction $mapStatus
    ) {}

    public function handle(Transaction $transaction, CallbackPayload $payload): bool
    {

        return $transaction->update([
            'transaction_id' => $payload->transactionID,
            'message_type' => $payload->messageType,
            'merchant_response' => $payload->merchantResponse,
            'response_code' => $payload->merchantRespCp,
            'fingerprint' => $payload->fingerprint,
            'payload' => $transaction->payload,
            'status' => $this->mapStatus->handle($payload->messageType),
        ]);
    }
}
