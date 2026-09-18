<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Support\Facades\Log;

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

        if (! in_array((string) $merchantResponse, $successType->expectedMerchantResponses(), true)) {
            $expected = implode(', ', $successType->expectedMerchantResponses());
            Log::warning(
                'SISP callback received known success message type with unexpected merchant response.',
                [
                    'messageType' => $messageType,
                    'merchantResponse' => $merchantResponse,
                    'expectedResponses' => $expected,
                ]
            );

            return TransactionStatus::pending;
        }

        return TransactionStatus::completed;
    }
}
