<?php

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Http\Requests\PaymentRequest;

class PaymentRequestController
{
    public function __invoke(PaymentRequest $request)
    {

        [$fields, $url] = $request->payment();

        return view('sisp::payment-request-form', compact('url', 'fields'));
    }
}
