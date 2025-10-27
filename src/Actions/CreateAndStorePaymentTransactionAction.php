<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\TransactionItemData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateAndStorePaymentTransactionAction
{
    public function __construct(
        private StorePaymentTransactionAction $storeTransaction,
        private StoreTransactionItemsAction $storeItems,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PaymentRequest $paymentRequest, Request $request): Transaction
    {
        return DB::transaction(function () use ($paymentRequest, $request) {

            $transaction = $this->storeTransaction->handle($paymentRequest);

            $itemsData = $this->getItemsData($request);

            $this->storeItems->handle($transaction, ...$itemsData);

            return $transaction;
        });
    }

    /**
     * Extract and convert items from request to ValueObjects.
     */
    private function getItemsData(Request $request): array
    {
        return TransactionItemData::collection(
            $request->array('items')
        );
    }
}
