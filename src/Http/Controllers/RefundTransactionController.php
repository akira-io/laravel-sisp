<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RecordRefundAction;
use Akira\Sisp\Http\Requests\RefundTransactionRequest;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\JsonResponse;
use LogicException;

final readonly class RefundTransactionController
{
    public function __construct(
        private RecordRefundAction $recordRefund,
    ) {}

    public function __invoke(Transaction $transaction, RefundTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->recordRefund->handle(
                $transaction,
                $request->refundAmount(),
                $request->refundReason(),
                $request->refundIdempotencyKey(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Refund recorded. Issue it in the SISP back office if you have not already.',
                'transaction' => $this->summary($transaction),
            ]);
        } catch (LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * What the caller needs to show the outcome. The full model carries the
     * customer's contact details and the decrypted payload, which an admin
     * client recording a refund has no use for.
     *
     * @return array<string, mixed>
     */
    private function summary(Transaction $transaction): array
    {
        return [
            'id' => $transaction->getKey(),
            'merchant_ref' => $transaction->merchant_ref,
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status->value,
            'merchant_response' => $transaction->merchant_response,
            'amount' => $transaction->amount,
            'refunded_amount' => $transaction->refundedAmount(),
            'refundable_amount' => $transaction->refundableAmount(),
            'refunded_at' => $transaction->refunded_at?->toIso8601String(),
        ];
    }
}
