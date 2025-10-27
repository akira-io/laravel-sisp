<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class SandboxController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $status = $request->query('status', 'success');

        $paymentData = PaymentRequestData::from([
            'amount' => (float) $request->query('amount', 0),
            'merchantRef' => $request->query('merchantRef'),
            'merchantSession' => $request->query('merchantSession'),
            'timeStamp' => $request->query('timeStamp'),
            'currency' => $request->query('currency'),
            'transactionCode' => $request->query('transactionCode'),
        ]);

        $sandboxPayload = Sisp::generateSandboxPayload($paymentData, $status);

        return redirect(route('sisp.callback'))->with($sandboxPayload->toArray());
    }
}
