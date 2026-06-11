<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Container\Attributes\Bind;

#[Bind(ValidatePaymentResponseFingerprintAction::class)]
interface CallbackFingerprintValidator
{
    public function handle(CallbackPayload $payload): bool;
}
