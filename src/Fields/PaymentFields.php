<?php

declare(strict_types=1);

namespace Akira\Sisp\Fields;

use Akira\Sisp\Contracts\Fields;
use Akira\Sisp\Facades\Sisp;

final class PaymentFields implements Fields
{
    private string $entityCode = '';

    private string $referenceNumber = '';

    private float|int $amount = 0;

    /**
     * Create a new PaymentFields instance.
     */
    public static function make(): self
    {

        return app(self::class);
    }

    /**
     * Set the payment amount.
     */
    public function withAmount(float|int $amount): self
    {

        $this->amount = $amount;

        return $this;
    }

    /**
     * Get the amount.
     */
    public function getAmount(): float|int
    {

        return $this->amount;
    }

    /**
     * Get the parsed amount.
     */
    public function parsedAmount(): int
    {
      
        return  intval(round($this->amount))*1000;

    }

    /**
     * Get the entity code.
     */
    public function getEntityCode(): string
    {

        return $this->entityCode;
    }

    /**
     * Get the reference number.
     */
    public function getReferenceNumber(): string
    {

        return $this->referenceNumber;
    }

    /**
     * Get array representation of the payment fields.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        
        return [
            'transactionCode' => Sisp::getDefaultTransactionCode(),
            'posID' => Sisp::getPosID(),
            'merchantRef' => Sisp::getMerchantReference(),
            'merchantSession' => Sisp::getMerchantSession(),
            'amount' => intval(round($this->amount)),
            'currency' => Sisp::getCurrency(),
            'is3DSec' => Sisp::getIs3DSec(),
            'urlMerchantResponse' => Sisp::getUrlMerchantResponse(),
            'languageMessages' => Sisp::getLanguageMessages(),
            'timeStamp' => Sisp::getTimeStamp(),
            'fingerprintversion' => Sisp::getFingerprintVersion(),
        ];
    }
}
