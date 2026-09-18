<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class ResolveCustomerErrorMessageAction
{
    public function handle(CallbackPayload $payload): ?string
    {
        foreach ([$payload->additionalErrorMessage, $payload->screenError, $payload->errorDescription] as $candidate) {
            if (mb_trim($candidate) !== '') {
                return mb_substr($candidate, 0, 255);
            }
        }

        return null;
    }
}
