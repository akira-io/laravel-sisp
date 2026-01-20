<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class PaymentRequestData
{
    public function __construct(
        public float $amount,
        public ?string $merchantRef = null,
        public ?string $merchantSession = null,
        public ?string $timeStamp = null,
        public ?string $currency = null,
        public ?string $transactionCode = null,
        public ?string $token = null,
        public ?string $entityCode = null,
        public ?string $referenceNumber = null,
        public ?string $locale = null,
        public ?string $customerEmail = null,
        public ?string $customerCountry = null,
        public ?string $customerCity = null,
        public ?string $customerAddress = null,
        public ?string $customerPostalCode = null,
        public ?string $customerPhone = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            merchantRef: $data['merchantRef'] ?? null,
            merchantSession: $data['merchantSession'] ?? null,
            timeStamp: $data['timeStamp'] ?? null,
            currency: $data['currency'] ?? null,
            transactionCode: $data['transactionCode'] ?? null,
            token: $data['token'] ?? null,
            entityCode: $data['entityCode'] ?? null,
            referenceNumber: $data['referenceNumber'] ?? null,
            locale: $data['locale'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            customerCountry: $data['customer_country'] ?? null,
            customerCity: $data['customer_city'] ?? null,
            customerAddress: $data['customer_address'] ?? null,
            customerPostalCode: $data['customer_postal_code'] ?? null,
            customerPhone: $data['customer_phone'] ?? null,
        );
    }

    public function hasThreeDSecureData(): bool
    {
        return $this->customerEmail !== null
            && $this->customerCountry !== null
            && $this->customerCity !== null
            && $this->customerAddress !== null
            && $this->customerPostalCode !== null;
    }

    /**
     * @return array<string>
     */
    public function getMissingThreeDSecureFields(): array
    {
        $missing = [];

        if ($this->customerEmail === null) {
            $missing[] = 'customer_email';
        }

        if ($this->customerCountry === null) {
            $missing[] = 'customer_country';
        }

        if ($this->customerCity === null) {
            $missing[] = 'customer_city';
        }

        if ($this->customerAddress === null) {
            $missing[] = 'customer_address';
        }

        if ($this->customerPostalCode === null) {
            $missing[] = 'customer_postal_code';
        }

        return $missing;
    }
}
