<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\FingerPrint;

use Akira\Sisp\Actions\PostAutCode;
use Akira\Sisp\Support\SispAmount;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class PaymentResponseFingerPrintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    public function handle(CallbackPayload $payload): string
    {
        $posAutCode = $this->postAutCode->handle();

        $amountThousandths = SispAmount::toThousandths($payload->amount);

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
