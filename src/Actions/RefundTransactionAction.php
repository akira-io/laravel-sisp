<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\TransactionRefunded;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\SispAmount;
use Akira\Sisp\Support\TransactionLogContext;
use Akira\Sisp\ValueObjects\RefundRequest;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class RefundTransactionAction
{
    public function __construct(
        private BuildRefundRequestAction $buildRefundRequest,
        private UpdateInvoiceStatusAction $updateInvoiceStatus,
    ) {}

    public function handle(
        Transaction $transaction,
        float $refundAmount,
        string $reason = 'user_refund',
    ): Transaction {
        throw_if($refundAmount <= 0, LogicException::class, 'Refund amount must be greater than 0.');

        $refunded = DB::transaction(function () use ($transaction, $refundAmount, $reason): Transaction {
            $locked = $transaction->newQuery()->whereKey($transaction->getKey())->lockForUpdate()->first();

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
            $status = $refundableThousandths === $refundThousandths
                ? TransactionStatus::refunded
                : TransactionStatus::completed;

            TransactionLogContext::run(
                'refund',
                fn (): bool => $locked->update([
                    'status' => $status->value,
                    'merchant_response' => "{$reason}::{$refundAmount}",
                    'payload' => $payload,
                    'refunded_at' => now(),
                ])
            );

            if ($status === TransactionStatus::refunded) {
                $this->updateInvoiceStatus->handle($locked, $status);
            }

            return $locked;
        });

        event(new TransactionRefunded($refunded, $refundAmount, $reason));

        return $refunded;
    }

    public function refundableAmount(Transaction $transaction): float
    {
        return $this->refundableThousandths($transaction) / 1000;
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
        if ($transaction->refunds()->exists()) {
            return (int) $transaction->refunds()
                ->get()
                ->sum(fn (Refund $refund): int => SispAmount::toThousandths($refund->amount));
        }

        return $this->legacyRefundedThousandths($transaction);
    }

    private function legacyRefundedThousandths(Transaction $transaction): int
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

        $transaction->refunds()->create([
            'amount' => (float) $request['amount'],
            'reason' => $reason,
            'request' => $request,
        ]);

        return $payload;
    }
}
