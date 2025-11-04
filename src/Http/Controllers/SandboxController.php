<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class SandboxController
{
    public function __invoke(Request $request): Response
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

        $callbackUrl = route('sisp.callback');
        $formHtml = '<!DOCTYPE html>';
        $formHtml .= '<html><head>';
        $formHtml .= '<title>SISP Sandbox - Processing</title>';
        $formHtml .= "<meta charset='utf-8'>";
        $formHtml .= '</head>';
        $formHtml .= "<body onload='document.forms[0].submit()'>";
        $formHtml .= "<form action='".htmlspecialchars($callbackUrl, ENT_QUOTES, 'UTF-8')."' method='post'>";

        foreach ($sandboxPayload->toArray() as $key => $value) {
            $formHtml .= "<input type='hidden' name='".htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8')."' value='".htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')."'>";
        }

        $formHtml .= '</form>';
        $formHtml .= '<noscript><p>JavaScript is disabled. <a href="#" onclick="document.forms[0].submit(); return false;">Click here</a> to continue.</p></noscript>';
        $formHtml .= '</body></html>';

        return response($formHtml)->header('Content-Type', 'text/html');
    }
}
