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
use Akira\Sisp\ValueObjects\RefundRequest;
use Illuminate\Support\Facades\DB;
use LogicException;

final readonly class RefundTransactionAction
{
    public function __construct(
        private BuildRefundRequestAction $buildRefundRequest,
        private UpdateInvoiceStatusAction $updateInvoiceStatus,
        private RefundLedger $ledger,
    ) {}

    public function handle(
        Transaction $transaction,
        float $refundAmount,
        string $reason = 'user_refund',
        ?string $idempotencyKey = null,
    ): Transaction {
        throw_if($refundAmount <= 0, LogicException::class, 'Refund amount must be greater than 0.');

        throw_if(
            $idempotencyKey !== null && ! $this->isValidIdempotencyKey($idempotencyKey),
            LogicException::class,
            'Idempotency key must be a non-blank string of at most 255 characters.'
        );

        [$refunded, $refund, $remainingThousandths] = DB::transaction(function () use ($transaction, $refundAmount, $reason, $idempotencyKey): array {
            $locked = $transaction->newQuery()->whereKey($transaction->getKey())->lockForUpdate()->first();

            if ($locked instanceof Transaction && $this->isReplay($locked, $refundAmount, $idempotencyKey)) {
                return [$locked, null, null];
            }

            if (! $locked instanceof Transaction || ! $this->canBeRefunded($locked)) {
                $status = $locked instanceof Transaction ? $locked->status->value : $transaction->status->value;

                throw new LogicException("Transaction with status '{$status}' cannot be refunded.");
            }

            $refundThousandths = SispAmount::toThousandths($refundAmount);
            $refundableThousandths = $this->ledger->refundableThousandths($locked);

            throw_if($refundThousandths <= 0, LogicException::class, 'Refund amount must be greater than 0.');

            throw_if(
                $refundThousandths > $refundableThousandths,
                LogicException::class,
                "Refund amount ({$refundAmount}) exceeds refundable balance."
            );

            $request = $this->buildRefundRequest($locked, $refundAmount);
            $payload = $this->appendRefundPayload($locked, $request->toArray(), $reason, $idempotencyKey);
            $refund = $this->recordRefund($locked, $request->toArray(), $reason, $idempotencyKey);
            $remainingThousandths = $refundableThousandths - $refundThousandths;
            $status = $this->ledger->isSettled($remainingThousandths)
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

            return [$locked, $refund, $remainingThousandths];
        });

        if ($refund instanceof Refund && $remainingThousandths !== null) {
            event(new TransactionRefunded(
                $refunded,
                $refundAmount,
                $reason,
                $refund,
                SispAmount::fromThousandths($this->ledger->isSettled($remainingThousandths) ? 0 : $remainingThousandths),
            ));
        }

        return $refunded;
    }

    public function refundableAmount(Transaction $transaction): float
    {
        return SispAmount::fromThousandths($this->ledger->refundableThousandths($transaction));
    }

    private function isValidIdempotencyKey(string $idempotencyKey): bool
    {
        return mb_trim($idempotencyKey) !== '' && mb_strlen($idempotencyKey) <= 255;
    }

    private function isReplay(Transaction $transaction, float $refundAmount, ?string $idempotencyKey): bool
    {
        if ($idempotencyKey === null) {
            return false;
        }

        $previous = $transaction->refunds()->where('idempotency_key', $idempotencyKey)->first();

        if ($previous === null) {
            return false;
        }

        throw_if(
            $previous->amount_thousandths !== SispAmount::toThousandths($refundAmount),
            LogicException::class,
            'Idempotency key was already used for a refund of a different amount.'
        );

        return true;
    }

    private function canBeRefunded(Transaction $transaction): bool
    {
        return $transaction->status->value === 'completed';
    }

    private function buildRefundRequest(Transaction $transaction, float $refundAmount): RefundRequest
    {
        $transactionAmount = SispAmount::toThousandths($transaction->amount);
        $alreadyRefunded = $this->ledger->refundedThousandths($transaction);
        $refundAmount = SispAmount::toThousandths($refundAmount);

        if ($alreadyRefunded === 0 && $refundAmount === $transactionAmount) {
            return $this->buildRefundRequest->total($transaction);
        }

        return $this->buildRefundRequest->partial($transaction, SispAmount::fromThousandths($refundAmount));
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
    private function appendRefundPayload(Transaction $transaction, array $request, string $reason, ?string $idempotencyKey): array
    {
        $payload = $transaction->getAttribute('payload');
        $payload = is_array($payload) ? $payload : [];
        $refunds = $payload['refunds'] ?? [];
        $refunds = is_array($refunds) ? $refunds : [];
        $refunds[] = array_filter([
            'amount' => $request['amount'],
            'reason' => $reason,
            'idempotency_key' => $idempotencyKey,
            'request' => $request,
        ], fn (mixed $value): bool => $value !== null);
        $payload['refunds'] = $refunds;

        return $payload;
    }

    /**
     * @param  array<string, float|string>  $request
     */
    private function recordRefund(Transaction $transaction, array $request, string $reason, ?string $idempotencyKey): Refund
    {
        $this->backfillLegacyRefunds($transaction);

        return $transaction->refunds()->create([
            'amount' => (float) $request['amount'],
            'reason' => $reason,
            'idempotency_key' => $idempotencyKey,
            'request' => $request,
        ]);
    }
}
