<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Callback;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use LogicException;

final class CallbackContext
{
    public ?Transaction $transaction = null;

    public ?string $failureReason = null;

    public function __construct(
        public readonly CallbackPayload $payload,
    ) {}

    public function transaction(): Transaction
    {
        return $this->transaction ?? throw new LogicException('The callback transaction has not been resolved yet.');
    }

    public function fail(string $reason): self
    {
        $this->failureReason = $reason;

        return $this;
    }

    public function failed(): bool
    {
        return $this->failureReason !== null;
    }
}
