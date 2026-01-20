<?php

declare(strict_types=1);

use Akira\Sisp\Exceptions\MissingThreeDSecureDataException;

it('creates exception with missing fields message', function (): void {
    $exception = new MissingThreeDSecureDataException(['customer_email', 'customer_city']);

    expect($exception)->toBeInstanceOf(Exception::class)
        ->and($exception->getMessage())->toBe('3D Secure is enabled but required customer data is missing: customer_email, customer_city');
});

it('handles single missing field', function (): void {
    $exception = new MissingThreeDSecureDataException(['customer_postal_code']);

    expect($exception->getMessage())->toContain('customer_postal_code');
});

it('handles multiple missing fields', function (): void {
    $exception = new MissingThreeDSecureDataException([
        'customer_email',
        'customer_country',
        'customer_city',
        'customer_address',
        'customer_postal_code',
    ]);

    expect($exception->getMessage())->toContain('customer_email')
        ->and($exception->getMessage())->toContain('customer_country')
        ->and($exception->getMessage())->toContain('customer_city')
        ->and($exception->getMessage())->toContain('customer_address')
        ->and($exception->getMessage())->toContain('customer_postal_code');
});
