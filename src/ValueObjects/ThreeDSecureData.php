<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

use Akira\Sisp\Support\CountryCodeMapper;

final readonly class ThreeDSecureData
{
    public function __construct(
        public string $email,
        public string $billAddrCountry,
        public string $billAddrCity,
        public string $billAddrLine1,
        public string $billAddrPostCode,
        public ?string $billAddrLine2 = null,
        public ?string $billAddrLine3 = null,
        public ?string $billAddrState = null,
        public ?string $mobilePhone = null,
    ) {}

    public static function fromCustomerData(
        string $email,
        string $country,
        string $city,
        string $address,
        string $postalCode,
        ?string $phone = null,
    ): self {
        return new self(
            email: $email,
            billAddrCountry: CountryCodeMapper::toNumeric($country),
            billAddrCity: $city,
            billAddrLine1: $address,
            billAddrPostCode: $postalCode,
            mobilePhone: $phone,
        );
    }
}
