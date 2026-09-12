<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class MapTransactionStatusAction
{
    public function handle(?string $messageType, ?string $merchantResponse = null): TransactionStatus
    {
        if ($messageType === CallbackPayload::ERROR_MESSAGE_TYPE) {
            return TransactionStatus::failed;
        }

        $successType = $messageType === null
            ? null
            : SuccessMessageType::tryFrom($messageType);

        if (! $successType instanceof SuccessMessageType) {
            return TransactionStatus::pending;
        }

        return in_array((string) $merchantResponse, $successType->expectedMerchantResponses(), true)
            ? TransactionStatus::completed
            : TransactionStatus::pending;
    }
}
