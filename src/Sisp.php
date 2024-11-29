<?php

namespace Akira\Sisp;

use Akira\Sisp\Actions\Fields\PaymentFields;
use Akira\Sisp\Actions\PaymentRequestUrl;
use Akira\Sisp\DTOs\PaymentRequestParams;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;

class Sisp
{
    // Build your next great package.

    public function getTransactions(): array|Collection
    {
        return Transaction::all();
    }
    
    
    /**
     * @param  float  $amount
     *
     * @return RedirectResponse|Redirector
     */
    public function requestPayment(float $amount): RedirectResponse|Redirector
    {
     
     return   to_route('sisp.payment.request', ['amount' => $amount]);
     
    }
}
