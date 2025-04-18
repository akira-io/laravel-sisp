<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\Transactions\UpdateTransactionAction;
use Akira\Sisp\Exceptions\InvalidPaymentResponseException;
use Akira\Sisp\Facades\Sisp;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PaymentResponseController
{
    /**
     * Handle the incoming request.
     *l
     *
     * @throws Exception
     */
    public function __invoke(Request $request, UpdateTransactionAction $action): View|Exception
    {
        return match (true) {
            Sisp::paymentRequestIsSuccess($request) => Sisp::processSuccessfulPayment($request, $action),
            Sisp::isCancelledByUser($request) => Sisp::handleUserCancellation($request),
            default => throw new InvalidPaymentResponseException(),
        };
    }
}
