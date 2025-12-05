<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CustomerData;
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
        private StoreCustomerDataAction $storeCustomerData,
        private GenerateInvoiceAction $generateInvoice,
        private GenerateInvoicePdfAction $generateInvoicePdf,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PaymentRequest $paymentRequest, Request $request): Transaction
    {
        return DB::transaction(function () use ($paymentRequest, $request): Transaction {

            $transaction = $this->storeTransaction->handle($paymentRequest);

            $customerData = CustomerData::from($request->all());

            $this->storeCustomerData->handle($transaction, $customerData);

            $itemsData = $this->getItemsData($request);

            $this->storeItems->handle($transaction, ...$itemsData);

            defer(
                fn () => $this->createAndGenerateInvoice($transaction)
            );

            return $transaction;
        });
    }

    private function createAndGenerateInvoice(Transaction $transaction): void
    {
        $invoice = $this->generateInvoice->handle($transaction); // @codeCoverageIgnore
        $invoice->load(['transaction' => function (\Illuminate\Database\Eloquent\Builder $query): void { // @codeCoverageIgnore
            $query->with('items'); // @codeCoverageIgnore
        }]);

        $this->generateInvoicePdf->handle($invoice); // @codeCoverageIgnore
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
