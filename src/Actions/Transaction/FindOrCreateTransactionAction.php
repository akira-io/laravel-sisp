<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class FindOrCreateTransactionAction
{
    public function __construct(private FindTransactionAttemptAction $findAttempt) {}

    public function handle(CallbackPayload $payload): Transaction
    {
        return $this->findAttempt->handle($payload)->transaction;
    }
}
