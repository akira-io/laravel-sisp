<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\GetTransactionStatusAction;
use Akira\Sisp\Http\Requests\TransactionStatusRequest;
use Illuminate\Http\JsonResponse;

final readonly class TransactionStatusController
{
    public function __construct(
        private GetTransactionStatusAction $getTransactionStatusAction
    ) {}

    public function __invoke(TransactionStatusRequest $request): JsonResponse
    {
        $transactionStatus = $this->getTransactionStatusAction->handle(
            $request->validated()
        );

        return response()->json($transactionStatus);
    }
}
