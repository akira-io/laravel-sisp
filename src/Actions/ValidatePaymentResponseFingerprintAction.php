<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\FingerPrint\PaymentErrorResponseFingerPrintAction;
use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\Contracts\CallbackFingerprintValidator;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class ValidatePaymentResponseFingerprintAction implements CallbackFingerprintValidator
{
    private PaymentErrorResponseFingerPrintAction $errorFingerPrint;

    public function __construct(
        private PaymentResponseFingerPrintAction $fingerPrint,
        ?PaymentErrorResponseFingerPrintAction $errorFingerPrint = null,
    ) {
        $this->errorFingerPrint = $errorFingerPrint ?? resolve(PaymentErrorResponseFingerPrintAction::class);
    }

    public function handle(CallbackPayload $payload): bool
    {
        $expected = $payload->isError()
            ? $this->errorFingerPrint->handle($payload)
            : $this->fingerPrint->handle($payload);

        return hash_equals($expected, $payload->fingerprint);
    }
}
