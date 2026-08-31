<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class CustomerData
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $country = null,
        public ?string $city = null,
        public ?string $address = null,
        public ?string $postalCode = null,
        public ?string $vat = null,
        public ?string $taxName = null,
        public ?string $taxEntityType = null,
        public ?string $taxAddress = null,
    ) {}

    public static function from(array $data): self
    {

        return new self(
            name: $data['customer_name'] ?? null,
            email: $data['customer_email'] ?? null,
            phone: $data['customer_phone'] ?? null,
            country: $data['customer_country'] ?? null,
            city: $data['customer_city'] ?? null,
            address: $data['customer_address'] ?? null,
            postalCode: $data['customer_postal_code'] ?? null,
            vat: $data['customer_vat'] ?? null,
            taxName: $data['customer_tax_name'] ?? null,
            taxEntityType: $data['customer_tax_entity_type'] ?? null,
            taxAddress: $data['customer_tax_address'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'customer_name' => $this->name,
            'customer_email' => $this->email,
            'customer_phone' => $this->phone,
            'customer_country' => $this->country,
            'customer_city' => $this->city,
            'customer_address' => $this->address,
            'customer_postal_code' => $this->postalCode,
            'customer_vat' => $this->vat,
            'customer_tax_name' => $this->taxName,
            'customer_tax_entity_type' => $this->taxEntityType,
            'customer_tax_address' => $this->taxAddress,
        ];
    }
}
