<?php

declare(strict_types=1);

namespace Akira\Sisp\Fields;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\Fields;

final class PaymentFields implements Fields
{
    private string $entityCode = '';

    private string $referenceNumber = '';

    private float|int $amount = 0;

    public function __construct(private readonly LoadConfig $config) {}

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
        return intval(round($this->amount)) * 1000;
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
            'transactionCode' => $this->config->getDefaultTransactionCode(),
            'posID' => $this->config->getPosId(),
            'merchantRef' => $this->config->getMerchantReference(),
            'merchantSession' => $this->config->getMerchantSession(),
            'amount' => intval(round($this->amount)),
            'currency' => $this->config->getCurrency(),
            'is3DSec' => $this->config->getIs3Dsec(),
            'urlMerchantResponse' => $this->config->getUrlMerchantResponse(),
            'languageMessages' => $this->config->getLanguageMessages(),
            'timeStamp' => $this->config->getTimeStamp(),
            'fingerprintversion' => $this->config->getFingerprintVersion(),
        ];
    }
}
