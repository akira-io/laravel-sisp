<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Akira\Sisp\Models\Transaction;

final readonly class RefundLedger
{
    public function refundedThousandths(Transaction $transaction): int
    {
        $fromPayload = array_sum(array_map(
            fn (array $refund): int => SispAmount::toThousandths($this->entryAmount($refund)),
            $this->payloadRefunds($transaction),
        ));

        if (! $this->recordsRefunds()) {
            return $fromPayload;
        }

        return max($fromPayload, (int) $transaction->refunds()->sum('amount_thousandths'));
    }

    public function refundableThousandths(Transaction $transaction): int
    {
        return max(0, SispAmount::toThousandths($transaction->amount) - $this->refundedThousandths($transaction));
    }

    /**
     * @return array<int, array<array-key, mixed>>
     */
    public function payloadRefunds(Transaction $transaction): array
    {
        $payload = $transaction->getAttribute('payload');
        $payload = is_array($payload) ? $payload : [];
        $refunds = $payload['refunds'] ?? [];

        if (! is_array($refunds)) {
            return [];
        }

        return array_values(array_filter($refunds, is_array(...)));
    }

    /**
     * @param  array<array-key, mixed>  $refund
     */
    public function entryAmount(array $refund): float
    {
        $amount = $refund['amount'] ?? 0;

        return is_numeric($amount) ? (float) $amount : 0.0;
    }

    public function recordsRefunds(): bool
    {
        return resolve(SispSchema::class)->hasRefundsTable();
    }
}
