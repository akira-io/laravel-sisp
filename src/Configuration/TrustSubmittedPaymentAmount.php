<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Akira\Sisp\Contracts\PaymentAmountResolver;
use Illuminate\Http\Request;

/**
 * The default: the package has no order or cart to check the amount against,
 * so the submitted amount is accepted. An application that exposes the HTTP
 * route binds its own PaymentAmountResolver, or verifies the amount in its
 * PaymentCompleted listener before fulfilling anything.
 */
final readonly class TrustSubmittedPaymentAmount implements PaymentAmountResolver
{
    public function expectedAmount(Request $request): ?float
    {
        return null;
    }
}
