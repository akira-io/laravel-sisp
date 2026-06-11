<?php

declare(strict_types=1);

namespace Akira\Sisp\Builders;

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Models\Transaction;
use LogicException;

final class RefundBuilder
{
    private ?float $amount = null;

    private string $reason = 'user_refund';

    public function __construct(
        private readonly RefundTransactionAction $refundTransaction,
        private readonly Transaction $transaction,
    ) {}

    public function amount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function full(): self
    {
        $this->amount = (float) $this->transaction->amount;

        return $this;
    }

    public function reason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function process(): Transaction
    {
        throw_if($this->amount === null, LogicException::class, 'A refund amount is required. Call amount() or full() first.');

        return $this->refundTransaction->handle($this->transaction, $this->amount, $this->reason);
    }
}
