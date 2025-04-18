<?php

declare(strict_types=1);

namespace Akira\Sisp\Events;

use Akira\Sisp\Transaction;
use Illuminate\Foundation\Events\Dispatchable;

final class SispPaymentRequestSuccess
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(public Transaction $transaction) {}
}
