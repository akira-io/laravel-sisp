<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\Configuration\TrustSubmittedPaymentAmount;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Http\Request;

/**
 * Tells POST /sisp/payment which amount the server expects for a checkout,
 * so the amount in the request body cannot be lowered by the browser.
 */
#[Bind(TrustSubmittedPaymentAmount::class)]
interface PaymentAmountResolver
{
    /**
     * The amount the application expects for this request, typically looked up
     * from the order or cart the request names (for example checkout_intent_id),
     * or null to accept the submitted amount.
     */
    public function expectedAmount(Request $request): ?float;
}
