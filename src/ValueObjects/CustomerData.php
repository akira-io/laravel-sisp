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
        ];
    }
}