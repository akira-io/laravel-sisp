<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\PaymentRequestUrlAction;
use Akira\Sisp\Actions\Transactions\StoreTransactionAction;
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

        $fields = PaymentFields::make()
            ->withAmount($request->float('amount'));

        $storeTransaction->handle(request: $request, fields: $fields->toArray());

        return view('sisp::payment-request-form', ['url' => $paymentRequestUrl->handle($fields), 'fields' => $fields->toArray()]);
    }
}
