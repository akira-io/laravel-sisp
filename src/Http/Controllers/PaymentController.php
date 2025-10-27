<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Actions\RenderPaymentFormAction;
use Akira\Sisp\Actions\StorePaymentTransactionAction;
use Akira\Sisp\Actions\StoreTransactionItemsAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Http\Requests\StorePaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\TransactionItemData;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class PaymentController
{
    public function __construct(
        private PreparePaymentAction $preparePayment,
        private StorePaymentTransactionAction $storeTransaction,
        private StoreTransactionItemsAction $storeItems,
        private RenderPaymentFormAction $renderForm,
        private LoadConfig $loadConfig,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(StorePaymentRequest $request)
    {
        $requestData = PaymentRequestData::from($request->validated());

        $paymentRequest = $this->preparePayment->handle($requestData);

        DB::transaction(function () use ($paymentRequest, $request) {

            $transaction = $this->storeTransaction->handle($paymentRequest);

            $itemsData = TransactionItemData::collection($request->array('items'));

            $this->storeItems->handle($transaction, ...$itemsData);
        });

        if ($this->loadConfig->shouldUseInertia()) {
            return $this->renderForm->renderInertia(
                $paymentRequest,
                $this->loadConfig->getPaymentFormComponent()
            );
        }

        return $this->renderForm->renderBlade($paymentRequest);
    }
}
