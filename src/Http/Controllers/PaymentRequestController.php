<?php

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\Fields\PaymentFields;
use Akira\Sisp\Actions\PaymentRequestUrl;
use Akira\Sisp\DTOs\PaymentRequestParams;

class PaymentRequestController
{
    public function __invoke()
    {

        $fields = PaymentFields::make()->withAmount(100);

        $paymentRequestParams = PaymentRequestParams::make($fields);

        //        dd($fields, $paymentRequestParams);

        $url = PaymentRequestUrl::make($paymentRequestParams)->url();

        $fields = $fields->toArray();

        //        dd($fields, $url);

        //        dd($fields, $url);

        return view('sisp::payment-request-form', compact('url', 'fields'));
    }
}
