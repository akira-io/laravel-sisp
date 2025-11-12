<?php

declare(strict_types=1);

namespace Akira\Sisp\Events;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Illuminate\Foundation\Events\Dispatchable;

final class PaymentPending
{
    use Dispatchable;

    public function __construct(
        public Transaction $transaction,
        public CallbackPayload $payload,
    ) {}
}
