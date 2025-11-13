<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class ValidatePaymentResponseFingerprintAction
{
    public function __construct(private PaymentResponseFingerPrintAction $fingerPrint) {}

    public function handle(CallbackPayload $payload): bool
    {
        return hash_equals(
            $this->fingerPrint->handle($payload),
            $payload->fingerprint
        );
    }
}
