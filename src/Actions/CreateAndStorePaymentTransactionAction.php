<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Exceptions\DuplicatePaymentIdentifierException;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\UniqueConstraintViolation;
use Akira\Sisp\ValueObjects\CustomerData;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\TransactionItemData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
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
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PaymentRequest $paymentRequest, Request $request): Transaction
    {
        try {
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
        } catch (QueryException $exception) {
            throw_if(UniqueConstraintViolation::causedBy($exception), DuplicatePaymentIdentifierException::class);

            throw $exception;
        }
    }

    private function createAndGenerateInvoice(Transaction $transaction): void
    {
        $invoice = $this->generateInvoice->handle($transaction); // @codeCoverageIgnore
        $invoice->load(['transaction' => function (Builder|BelongsTo $query): void { // @codeCoverageIgnore
            $query->with('items'); // @codeCoverageIgnore
        }]); // @codeCoverageIgnore
    }

    private function getItemsData(Request $request): array
    {
        return TransactionItemData::collection(
            $request->array('items')
        );
    }
}
