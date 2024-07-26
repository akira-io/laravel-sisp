<?php

declare(strict_types=1);

namespace Akira\Sisp\DTOs;

use Akira\Sisp\Actions\Fields\PaymentFields;
use Akira\Sisp\Actions\FingerPrints\PaymentRequestFingerPrint;

class PaymentRequestParams
{
    public string $fingerPrint;

    public string $timeStamp;

    public mixed $fingerPrintVersion;

    public function __construct(PaymentFields $field)
    {
        $this->fingerPrint = PaymentRequestFingerPrint::make($field)->get();
        $this->timeStamp = $field->getTimeStamp();
        $this->fingerPrintVersion = $field->getFingerprintVersion();
    }

    public static function make(PaymentFields $field): self
    {
        return app(self::class, compact('field'));
    }
}
