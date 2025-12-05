<?php

declare(strict_types=1);

use Akira\Sisp\ValueObjects\CustomerData;

it('creates instance with all fields', function (): void {
    $customer = new CustomerData(
        name: 'John Doe',
        email: 'john@example.com',
        phone: '+238123456789',
        country: 'CV',
        city: 'Praia',
        address: '123 Main St',
    );

    expect($customer)->toBeInstanceOf(CustomerData::class)
        ->and($customer->name)->toBe('John Doe')
        ->and($customer->email)->toBe('john@example.com')
        ->and($customer->phone)->toBe('+238123456789')
        ->and($customer->country)->toBe('CV')
        ->and($customer->city)->toBe('Praia')
        ->and($customer->address)->toBe('123 Main St');
});

it('creates instance with only required fields', function (): void {
    $customer = new CustomerData(name: 'Jane Doe');

    expect($customer->name)->toBe('Jane Doe')
        ->and($customer->email)->toBeNull()
        ->and($customer->phone)->toBeNull()
        ->and($customer->country)->toBeNull()
        ->and($customer->city)->toBeNull()
        ->and($customer->address)->toBeNull();
});

it('creates instance from array with all fields', function (): void {
    $data = [
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '+238123456789',
        'customer_country' => 'CV',
        'customer_city' => 'Praia',
        'customer_address' => '123 Main St',
    ];

    $customer = CustomerData::from($data);

    expect($customer->name)->toBe('John Doe')
        ->and($customer->email)->toBe('john@example.com')
        ->and($customer->phone)->toBe('+238123456789')
        ->and($customer->country)->toBe('CV')
        ->and($customer->city)->toBe('Praia')
        ->and($customer->address)->toBe('123 Main St');
});

it('creates instance from array with missing fields', function (): void {
    $data = [
        'customer_name' => 'Jane Doe',
    ];

    $customer = CustomerData::from($data);

    expect($customer->name)->toBe('Jane Doe')
        ->and($customer->email)->toBeNull()
        ->and($customer->phone)->toBeNull()
        ->and($customer->country)->toBeNull()
        ->and($customer->city)->toBeNull()
        ->and($customer->address)->toBeNull();
});

it('converts to array correctly', function (): void {
    $customer = new CustomerData(
        name: 'John Doe',
        email: 'john@example.com',
        phone: '+238123456789',
        country: 'CV',
        city: 'Praia',
        address: '123 Main St',
    );

    $array = $customer->toArray();

    expect($array)->toBeArray()
        ->toHaveKeys([
            'customer_name',
            'customer_email',
            'customer_phone',
            'customer_country',
            'customer_city',
            'customer_address',
        ])
        ->and($array['customer_name'])->toBe('John Doe')
        ->and($array['customer_email'])->toBe('john@example.com')
        ->and($array['customer_phone'])->toBe('+238123456789')
        ->and($array['customer_country'])->toBe('CV')
        ->and($array['customer_city'])->toBe('Praia')
        ->and($array['customer_address'])->toBe('123 Main St');
});

it('converts to array with null values', function (): void {
    $customer = new CustomerData(name: 'John Doe');

    $array = $customer->toArray();

    expect($array['customer_name'])->toBe('John Doe')
        ->and($array['customer_email'])->toBeNull()
        ->and($array['customer_phone'])->toBeNull()
        ->and($array['customer_country'])->toBeNull()
        ->and($array['customer_city'])->toBeNull()
        ->and($array['customer_address'])->toBeNull();
});

it('maintains data integrity on array conversion', function (): void {
    $data = [
        'customer_name' => 'Alice Smith',
        'customer_email' => 'alice@example.com',
        'customer_phone' => '+238987654321',
        'customer_country' => 'PT',
        'customer_city' => 'Lisboa',
        'customer_address' => '456 Side St',
    ];

    $customer = CustomerData::from($data);
    $converted = $customer->toArray();

    expect($converted)->toBe($data);
});

it('is readonly and immutable', function (): void {
    $customer = new CustomerData(
        name: 'John Doe',
        email: 'john@example.com',
    );

    expect($customer->name)->toBe('John Doe')
        ->and($customer->email)->toBe('john@example.com');
});
