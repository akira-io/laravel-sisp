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
