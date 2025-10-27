<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\PaymentRequestUrlAction;
use Akira\Sisp\Actions\Transactions\StoreTransactionAction;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Fields\PaymentFields;
use Akira\Sisp\Http\Requests\PaymentRequest;
use Exception;
use Illuminate\Contracts\View\View;

final class PaymentRequestController
{
    /**
     * Handle the incoming request.
     *
     * @throws Exception
     */
    public function __invoke(PaymentRequest $request, StoreTransactionAction $storeTransaction, PaymentRequestUrlAction $paymentRequestUrl): View
    {
        
        $fields = PaymentFields::make()->withAmount($request->float('amount'));

        $storeTransaction->handle(
            transactionId: $request->validated('transactionId'),
            amount : $request->float('amount'),
            details : $request->array('details'),
        );

        return view(
            view: Sisp::getPaymentRequestForm(),
            data: [
                'url' => $paymentRequestUrl->handle($fields),
                'fields' => $fields->toArray(),
            ]);
    }
}
