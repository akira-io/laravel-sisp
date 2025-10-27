<?php

declare(strict_types=1);

namespace Akira\Sisp\FingerPrints;

use Akira\Sisp\Actions\PostAutCode;
use Akira\Sisp\Contracts\Fields;
use Akira\Sisp\Contracts\FingerPrint;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Fields\PaymentFields;

final readonly class PaymentRequestFingerPrint implements FingerPrint
{
    /**
     * Create a new PaymentRequestFingerPrint instance.
     *
     * @param  PaymentFields  $field
     */
    public function __construct(private Fields $field) {}

    /**
     * Create a new PaymentRequestFingerPrint instance.
     *
     * @param  PaymentFields  $field
     */
    public static function make(Fields $field): self
    {

        return app(self::class, ['field' => $field]);
    }

    /**
     * Get the fingerprint.
     */
    public function get(): string
    {
        return base64_encode(hash('sha512', $this->getFingerPrintContent(), true));
    }

    /**
     * Get the fingerprint content.
     */
    private function getFingerPrintContent(): string
    {
        
        return PostAutCode::encode()
            .Sisp::getTimeStamp()
            .$this->field->parsedAmount()
            .Sisp::getMerchantReference()
            .Sisp::getMerchantSession()
            .Sisp::getPosID()
            .Sisp::getCurrency()
            .Sisp::getDefaultTransactionCode();
    }
}
