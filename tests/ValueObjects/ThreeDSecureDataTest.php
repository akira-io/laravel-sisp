<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\ThreeDSecureData;

it('constructs with all required fields', function (): void {
    $data = new ThreeDSecureData(
        email: 'test@example.com',
        billAddrCountry: '132',
        billAddrCity: 'Praia',
        billAddrLine1: 'Rua Principal 123',
        billAddrPostCode: '7600',
    );

    expect($data->email)->toBe('test@example.com')
        ->and($data->billAddrCountry)->toBe('132')
        ->and($data->billAddrCity)->toBe('Praia')
        ->and($data->billAddrLine1)->toBe('Rua Principal 123')
        ->and($data->billAddrPostCode)->toBe('7600')
        ->and($data->billAddrLine2)->toBeNull()
        ->and($data->billAddrLine3)->toBeNull()
        ->and($data->billAddrState)->toBeNull()
        ->and($data->mobilePhone)->toBeNull();
});

it('constructs with optional fields', function (): void {
    $data = new ThreeDSecureData(
        email: 'test@example.com',
        billAddrCountry: '132',
        billAddrCity: 'Praia',
        billAddrLine1: 'Rua Principal 123',
        billAddrPostCode: '7600',
        billAddrLine2: 'Apt 4B',
        billAddrLine3: 'Floor 2',
        billAddrState: 'SV',
        mobilePhone: '9123456',
    );

    expect($data->billAddrLine2)->toBe('Apt 4B')
        ->and($data->billAddrLine3)->toBe('Floor 2')
        ->and($data->billAddrState)->toBe('SV')
        ->and($data->mobilePhone)->toBe('9123456');
});

it('creates from customer data and converts country code', function (): void {
    $data = ThreeDSecureData::fromCustomerData(
        email: 'customer@example.com',
        country: 'cv',
        city: 'Mindelo',
        address: 'Avenida Marginal',
        postalCode: '2110',
        phone: '9876543',
    );

    expect($data->email)->toBe('customer@example.com')
        ->and($data->billAddrCountry)->toBe('132') // CV converted to 132
        ->and($data->billAddrCity)->toBe('Mindelo')
        ->and($data->billAddrLine1)->toBe('Avenida Marginal')
        ->and($data->billAddrPostCode)->toBe('2110')
        ->and($data->mobilePhone)->toBe('9876543');
});

it('creates from customer data without phone', function (): void {
    $data = ThreeDSecureData::fromCustomerData(
        email: 'user@test.com',
        country: 'PT',
        city: 'Lisboa',
        address: 'Rua Augusta',
        postalCode: '1100-053',
    );

    expect($data->billAddrCountry)->toBe('620') // PT converted to 620
        ->and($data->mobilePhone)->toBeNull();
});
