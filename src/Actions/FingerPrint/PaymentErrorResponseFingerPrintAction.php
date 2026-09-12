<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\FingerPrint;

use Akira\Sisp\Actions\PostAutCode;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class PaymentErrorResponseFingerPrintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    public function handle(CallbackPayload $payload): string
    {
        $fields = [
            $this->postAutCode->handle(),
            $payload->messageType,
            $payload->messageID,
            $payload->errorCode,
            $payload->errorDetail,
            $payload->errorDescription,
            $payload->merchantRef,
            $payload->merchantSession,
            $payload->additionalErrorMessage,
            $payload->timeStamp,
        ];

        return base64_encode(hash('sha512', implode('', $fields), true));
    }
}
