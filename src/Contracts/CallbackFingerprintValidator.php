<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\ValueObjects\CallbackPayload;

interface CallbackFingerprintValidator
{
    public function handle(CallbackPayload $payload): bool;
}
