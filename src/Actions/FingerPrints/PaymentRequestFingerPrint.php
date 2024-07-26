<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\FingerPrints;

use Akira\Sisp\Actions\Fields\PaymentFields;
use Akira\Sisp\Actions\PostAutCode;

class PaymentRequestFingerPrint
{
    public function __construct(protected PaymentFields $field) {}

    public static function make(PaymentFields $field): self
    {
        return app(self::class, compact('field'));
    }

    public function get(): string
    {
        return base64_encode(hash('sha512', $this->getFingerPrintContent(), true));
    }

    private function getFingerPrintContent(): string
    {
        return PostAutCode::encode()
            .$this->field->getTimeStamp()
            .$this->field->parsedAmount()
            .$this->field->getMerchantRef()
            .$this->field->getMerchantSession()
            .$this->field->getPosID()
            .$this->field->getCurrency()
            .$this->field->getTransactionCode();
    }
}
