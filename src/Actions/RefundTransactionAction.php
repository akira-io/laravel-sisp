<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\SispAmount;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\ValueObjects\RefundRequest;
use LogicException;

final readonly class RefundTransactionAction
{
    public function __construct(private BuildRefundRequestAction $buildRefundRequest) {}

    public function handle(
        Transaction $transaction,
        float $refundAmount,
        string $reason = 'user_refund',
    ): Transaction {
        if (! $this->canBeRefunded($transaction)) {
            throw new LogicException(
                "Transaction with status '{$transaction->status->value}' cannot be refunded."
            );
        }

        throw_if($refundAmount <= 0, LogicException::class, 'Refund amount must be greater than 0.');

        $refundThousandths = SispAmount::toThousandths($refundAmount);
        $refundableThousandths = $this->refundableThousandths($transaction);

        throw_if($refundThousandths <= 0, LogicException::class, 'Refund amount must be greater than 0.');

        throw_if(
            $refundThousandths > $refundableThousandths,
            LogicException::class,
            "Refund amount ({$refundAmount}) exceeds refundable balance."
        );

        $request = $this->buildRefundRequest($transaction, $refundAmount);
        $payload = $this->appendRefundPayload($transaction, $request->toArray(), $reason);
        $remainingThousandths = $refundableThousandths - $refundThousandths;

        TransactionLogContext::run(
            'refund',
            fn (): bool => $transaction->update([
                'status' => $remainingThousandths === 0 ? TransactionStatus::refunded->value : TransactionStatus::completed->value,
                'merchant_response' => "{$reason}::{$refundAmount}",
                'payload' => $payload,
                'refunded_at' => now(),
            ])
        );

        event(new TransactionRefunded($transaction, $refundAmount, $reason));

        return $transaction;
    }

    private function canBeRefunded(Transaction $transaction): bool
    {
        return $transaction->status->value === 'completed';
    }

    private function buildRefundRequest(Transaction $transaction, float $refundAmount): RefundRequest
    {
        $transactionAmount = SispAmount::toThousandths($transaction->amount);
        $alreadyRefunded = $this->refundedThousandths($transaction);
        $refundAmount = SispAmount::toThousandths($refundAmount);

        if ($alreadyRefunded === 0 && $refundAmount === $transactionAmount) {
            return $this->buildRefundRequest->total($transaction);
        }

        return $this->buildRefundRequest->partial($transaction, $refundAmount / 1000);
    }

    private function refundableThousandths(Transaction $transaction): int
    {
        return max(0, SispAmount::toThousandths($transaction->amount) - $this->refundedThousandths($transaction));
    }

    private function refundedThousandths(Transaction $transaction): int
    {
        $payload = $transaction->getAttribute('payload');
        $payload = is_array($payload) ? $payload : [];
        $refunds = $payload['refunds'] ?? [];
        $refunds = is_array($refunds) ? $refunds : [];

        return array_sum(array_map(
            fn (mixed $refund): int => is_array($refund) ? SispAmount::toThousandths($refund['amount'] ?? 0) : 0,
            $refunds,
        ));
    }

    /**
     * @param  array<string, float|string>  $request
     * @return array<string, mixed>
     */
    private function appendRefundPayload(Transaction $transaction, array $request, string $reason): array
    {
        $payload = $transaction->getAttribute('payload');
        $payload = is_array($payload) ? $payload : [];
        $refunds = $payload['refunds'] ?? [];
        $refunds = is_array($refunds) ? $refunds : [];
        $refunds[] = [
            'amount' => $request['amount'],
            'reason' => $reason,
            'request' => $request,
        ];
        $payload['refunds'] = $refunds;

        return $payload;
    }
}
