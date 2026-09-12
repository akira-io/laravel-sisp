<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Http\Requests\RefundTransactionRequest;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\JsonResponse;
use LogicException;

final readonly class RefundTransactionController
{
    public function __construct(
        private RefundTransactionAction $refundTransaction,
    ) {}

    public function __invoke(Transaction $transaction, RefundTransactionRequest $request): JsonResponse
    {
        try {
            $transaction = $this->refundTransaction->handle(
                $transaction,
                $request->refundAmount(),
                $request->refundReason(),
            );

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
