<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;

final class FindOrCreateTransactionAction
{
    public function handle(CallbackPayload $payload): Transaction
    {
        return Transaction::query()
            ->where('merchant_ref', $payload->merchantRef)
            ->where('merchant_session', $payload->merchantSession)
            ->firstOrFail();
    }
}
