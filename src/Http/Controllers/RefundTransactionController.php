<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final readonly class RefundTransactionController
{
    public function __construct(
        private RefundTransactionAction $refundTransaction,
    ) {}

    public function __invoke(Transaction $transaction, Request $request): JsonResponse
    {
        $refundAmount = (float) $request->input('amount');
        $reason = $request->input('reason', 'user_refund');

        try {
            $this->refundTransaction->handle($transaction, $refundAmount, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Transaction refunded successfully.',
                'transaction' => $transaction,
            ]);
        } catch (LogicException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
