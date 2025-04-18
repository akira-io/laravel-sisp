<?php

declare(strict_types=1);

namespace Akira\Sisp\Concerns;

use Akira\Sisp\Enums\SuccessMessageType;
use Illuminate\Http\Request;

trait Payment
{
    /**
     * Check if the payment request was successful.
     */
    public function paymentRequestIsSuccess(Request $request): bool
    {

        return $request->has('messageType')
            && $request->messageType === SuccessMessageType::purchase->value;
    }
}
