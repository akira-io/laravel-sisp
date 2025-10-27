<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Akira\Sisp\Actions\Transactions\UpdateTransactionAction;
use Akira\Sisp\Concerns\Support;
use Akira\Sisp\Events\SispPaymentCancelledByUser;
use Akira\Sisp\Events\SispPaymentRequestSuccess;
use Akira\Sisp\Exceptions\TransactionNotFoundException;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

final class Sisp
{
    use Support;

    /**
     * Get all transactions from the database.
     *
     * @return Collection<int,Transaction>
     */
    public function getTransactions(): Collection
    {
        return Transaction::get();
    }

    /**
     * Request a payment to the SISP Gateway.
     *
     * @param  array<string,mixed>  $options
     *
     * @throws Exception
     */
    public function requestPayment(float $amount, string $transactionId, array $details = []): RedirectResponse|Redirector
    {
        return to_route('sisp.payment.request',
            [
                'amount' => $amount,
                'transactionId' => $transactionId,
                'details' => $details,
            ]
        );
    }

    /**
     * Handle the success payment response from SISP.
     *
     * @throws TransactionNotFoundException
     */
    public function processSuccessfulPayment(Request $request, UpdateTransactionAction $action): View
    {
        $transaction = $action->handle(request: $request);

        if (! $transaction instanceof Transaction) {
            throw new TransactionNotFoundException();
        }

        SispPaymentRequestSuccess::dispatch($transaction);

        /** @var view-string $viewName */
        $viewName = 'sisp::purchase-success';

        return view($viewName, [
            'message' => $request->all(),
        ]);
    }

    /**
     * Handle the cancellation of the payment by the user.
     */
    public function handleUserCancellation(Request $request): View
    {
        SispPaymentCancelledByUser::dispatch($request->all());

        /** @var view-string $viewName */
        $viewName = 'sisp::purchase-cancelled';

        return view($viewName, [
            'message' => $request->all(),
        ]);
    }
}
