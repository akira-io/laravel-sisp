<?php

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Enums\SuccessMessageType;
use Illuminate\Http\Request;

class PaymentResponseController
{
    public function __invoke(Request $request)
    {
        if ($request->has('messageType') && $request->messageType == SuccessMessageType::purchase->value) {
            return view('sisp::purchase-success', [
                'message' => $request->all(),
            ]);
        }
        //        dd($request->all());
    }
}
