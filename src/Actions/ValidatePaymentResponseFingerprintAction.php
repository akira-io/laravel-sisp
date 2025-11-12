<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class ValidatePaymentResponseFingerprintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    public function handle(CallbackPayload $payload): bool
    {
        return hash_equals(
            $this->computeFingerprint($payload),
            $payload->fingerprint
        );
    }

    private function computeFingerprint(CallbackPayload $payload): string
    {
        $posAutCode = $this->postAutCode->handle();
        $amountThousandths = (int) ((float) $payload->amount * 1000);

        $fields = [
            $posAutCode,
            $payload->messageType,
            $payload->clearingPeriod,
            $payload->transactionID,
            $payload->merchantRef,
            $payload->merchantSession,
            $amountThousandths,
            $payload->messageID,
            $payload->pan,
            $payload->merchantResponse,
            $payload->timeStamp,
            $payload->reference,
            $payload->entityCode,
            $payload->clientReceipt,
            $payload->additionalErrorMessage,
            $payload->reloadCode,
        ];

        return base64_encode(hash('sha512', implode('', $fields), true));
    }
}
