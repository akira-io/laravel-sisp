<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Fields\PaymentFields;
use Akira\Sisp\FingerPrints\PaymentRequestFingerPrint;
use Exception;
use Illuminate\Support\Uri;

final class PaymentRequestUrlAction
{
    /**
     * Handle the payment request Url
     *
     * @throws Exception
     */
    public function handle(PaymentFields $field): string
    {
        return (string) Uri::of(Sisp::getUri())
            ->withQuery(['FingerPrint' => PaymentRequestFingerPrint::make($field)->get()])
            ->withQuery(['TimeStamp' => Sisp::getTimeStamp()])
            ->withQuery(['FingerPrintVersion' => Sisp::getFingerprintVersion()]);
    }
}
