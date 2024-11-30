<?php

namespace Akira\Sisp;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class Sisp
{
    // Build your next great package.

    public function getTransactions(): array|Collection
    {
        return Transaction::all();
    }

    public function requestPayment(float $amount): RedirectResponse|Redirector
    {

        return to_route('sisp.payment.request', ['amount' => $amount]);

    }
}
