<?php

declare(strict_types=1);

namespace Akira\Sisp\FingerPrints;

use Akira\Sisp\Actions\PostAutCode;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\Fields;
use Akira\Sisp\Contracts\FingerPrint;

final readonly class PaymentRequestFingerPrint implements FingerPrint
{
    public function __construct(
        private Fields $field,
        private PostAutCode $postAutCode,
        private LoadConfig $config,
    ) {}

    /**
     * Create a new PaymentRequestFingerPrint instance.
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
        return $this->postAutCode->handle()
            . $this->config->getTimeStamp()
            . $this->field->parsedAmount()
            . $this->config->getMerchantReference()
            . $this->config->getMerchantSession()
            . $this->config->getPosId()
            . $this->config->getCurrency()
            . $this->config->getDefaultTransactionCode();
    }
}
