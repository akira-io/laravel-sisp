<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\RefundLedger;
use Akira\Sisp\Support\SispAmount;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\Support\TransactionRowLock;
use Akira\Sisp\ValueObjects\RefundRequest;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class RefundTransactionAction
{
    private RefundLedger $ledger;

    public function __construct(
        private BuildRefundRequestAction $buildRefundRequest,
        ?RefundLedger $ledger = null,
    ) {
        $this->ledger = $ledger ?? resolve(RefundLedger::class);
    }

    public function handle(
        Transaction $transaction,
        float $refundAmount,
        string $reason = 'user_refund',
    ): Transaction {
        throw_if($refundAmount <= 0, LogicException::class, 'Refund amount must be greater than 0.');

        [$refund, $remainingThousandths] = DB::transaction(function () use ($transaction, $refundAmount, $reason): array {
            $locked = TransactionRowLock::acquire($transaction);

            if (! $locked instanceof Transaction || ! $this->canBeRefunded($locked)) {
                $status = $locked instanceof Transaction ? $locked->status->value : $transaction->status->value;

                throw new LogicException("Transaction with status '{$status}' cannot be refunded.");
            }

            $refundThousandths = SispAmount::toThousandths($refundAmount);
            $refundableThousandths = $this->refundableThousandths($locked);

            throw_if($refundThousandths <= 0, LogicException::class, 'Refund amount must be greater than 0.');

            throw_if(
                $refundThousandths > $refundableThousandths,
                LogicException::class,
                "Refund amount ({$refundAmount}) exceeds refundable balance."
            );

            $request = $this->buildRefundRequest($locked, $refundAmount);
            $payload = $this->appendRefundPayload($locked, $request->toArray(), $reason);
            $refund = $this->recordRefund($locked, $request->toArray(), $reason);
            $remainingThousandths = $refundableThousandths - $refundThousandths;

            TransactionRowLock::adopt($transaction, $locked);

            TransactionLogContext::run(
                'refund',
                fn (): bool => $transaction->update([
                    'status' => $remainingThousandths === 0 ? TransactionStatus::refunded->value : TransactionStatus::completed->value,
                    'merchant_response' => "{$reason}::{$refundAmount}",
                    'payload' => $payload,
                    'refunded_at' => now(),
                ])
            );

            return [$refund, $remainingThousandths];
        });

        event(new TransactionRefunded(
            $transaction,
            $refundAmount,
            $reason,
            $refund,
            SispAmount::fromThousandths($remainingThousandths),
        ));

        return $transaction;
    }

    public function refundableAmount(Transaction $transaction): float
    {
        return SispAmount::fromThousandths($this->refundableThousandths($transaction));
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

        return $this->buildRefundRequest->partial($transaction, SispAmount::fromThousandths($refundAmount));
    }

    private function refundableThousandths(Transaction $transaction): int
    {
        return $this->ledger->refundableThousandths($transaction);
    }

    private function refundedThousandths(Transaction $transaction): int
    {
        return $this->ledger->refundedThousandths($transaction);
    }

    private function backfillLegacyRefunds(Transaction $transaction): void
    {
        if ($transaction->refunds()->exists()) {
            return;
        }

        foreach ($this->ledger->payloadRefunds($transaction) as $refund) {
            $reason = $refund['reason'] ?? null;
            $request = $refund['request'] ?? [];

            $transaction->refunds()->create([
                'amount' => $this->ledger->entryAmount($refund),
                'reason' => is_string($reason) ? $reason : null,
                'request' => is_array($request) ? $request : [],
            ]);
        }
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

    /**
     * @param  array<string, float|string>  $request
     */
    private function recordRefund(Transaction $transaction, array $request, string $reason): ?Refund
    {
        if (! $this->ledger->recordsRefunds()) {
            return null;
        }

        $this->backfillLegacyRefunds($transaction);

        return $transaction->refunds()->create([
            'amount' => (float) $request['amount'],
            'reason' => $reason,
            'request' => $request,
        ]);
    }
}
