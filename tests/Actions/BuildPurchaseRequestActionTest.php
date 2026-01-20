<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildPurchaseRequestAction;
use Akira\Sisp\ValueObjects\ThreeDSecureData;

it('generates valid base64 encoded JSON', function (): void {
    $action = resolve(BuildPurchaseRequestAction::class);

    $data = new ThreeDSecureData(
        email: 'test@example.com',
        billAddrCountry: '132',
        billAddrCity: 'Praia',
        billAddrLine1: 'Rua Principal 123',
        billAddrPostCode: '7600',
        mobilePhone: '9123456',
    );

    $result = $action->handle($data);

    // Verify it's base64
    expect($result)->toBeString()
        ->and(base64_decode($result, true))->not->toBeFalse();

    // Decode and verify structure
    $decoded = json_decode(base64_decode($result), true);

    expect($decoded)->toBeArray()
        ->and($decoded)->toHaveKeys([
            'acctID', 'acctInfo', 'email', 'addrMatch',
            'billAddrCity', 'billAddrCountry', 'billAddrLine1', 'billAddrLine2', 'billAddrLine3',
            'billAddrPostCode', 'billAddrState',
            'shipAddrCity', 'shipAddrCountry', 'shipAddrLine1', 'shipAddrPostCode', 'shipAddrState',
            'workPhone', 'mobilePhone',
        ]);
});

it('includes correct customer data in payload', function (): void {
    $action = resolve(BuildPurchaseRequestAction::class);

    $data = new ThreeDSecureData(
        email: 'customer@test.com',
        billAddrCountry: '620',
        billAddrCity: 'Lisboa',
        billAddrLine1: 'Avenida da Liberdade',
        billAddrPostCode: '1250-096',
        billAddrLine2: 'Edificio 5',
        mobilePhone: '912345678',
    );

    $result = $action->handle($data);
    $decoded = json_decode(base64_decode($result), true);

    expect($decoded['email'])->toBe('customer@test.com')
        ->and($decoded['billAddrCountry'])->toBe('620')
        ->and($decoded['billAddrCity'])->toBe('Lisboa')
        ->and($decoded['billAddrLine1'])->toBe('Avenida da Liberdade')
        ->and($decoded['billAddrLine2'])->toBe('Edificio 5')
        ->and($decoded['billAddrPostCode'])->toBe('1250-096')
        ->and($decoded['mobilePhone']['subscriber'])->toBe('912345678');
});

it('includes account info with current date', function (): void {
    $action = resolve(BuildPurchaseRequestAction::class);

    $data = new ThreeDSecureData(
        email: 'test@example.com',
        billAddrCountry: '132',
        billAddrCity: 'Praia',
        billAddrLine1: 'Address',
        billAddrPostCode: '7600',
    );

    $result = $action->handle($data);
    $decoded = json_decode(base64_decode($result), true);

    $expectedDate = now()->format('Ymd');

    expect($decoded['acctInfo'])->toBeArray()
        ->and($decoded['acctInfo']['chAccAgeInd'])->toBe('05')
        ->and($decoded['acctInfo']['chAccChange'])->toBe($expectedDate)
        ->and($decoded['acctInfo']['chAccDate'])->toBe($expectedDate)
        ->and($decoded['acctInfo']['chAccPwChange'])->toBe($expectedDate)
        ->and($decoded['acctInfo']['chAccPwChangeInd'])->toBe('05')
        ->and($decoded['acctInfo']['suspiciousAccActivity'])->toBe('01');
});

it('uses default phone when not provided', function (): void {
    $action = resolve(BuildPurchaseRequestAction::class);

    $data = new ThreeDSecureData(
        email: 'test@example.com',
        billAddrCountry: '132',
        billAddrCity: 'Praia',
        billAddrLine1: 'Address',
        billAddrPostCode: '7600',
    );

    $result = $action->handle($data);
    $decoded = json_decode(base64_decode($result), true);

    expect($decoded['mobilePhone'])->toBe(['cc' => '238', 'subscriber' => '0000000'])
        ->and($decoded['workPhone'])->toBe(['cc' => '238', 'subscriber' => '0000000']);
});

it('includes shipping address defaults', function (): void {
    $action = resolve(BuildPurchaseRequestAction::class);

    $data = new ThreeDSecureData(
        email: 'test@example.com',
        billAddrCountry: '132',
        billAddrCity: 'Praia',
        billAddrLine1: 'Address',
        billAddrPostCode: '7600',
    );

    $result = $action->handle($data);
    $decoded = json_decode(base64_decode($result), true);

    expect($decoded['shipAddrCity'])->toBe('City')
        ->and($decoded['shipAddrCountry'])->toBe('132')
        ->and($decoded['shipAddrLine1'])->toBe('000')
        ->and($decoded['shipAddrPostCode'])->toBe('000')
        ->and($decoded['shipAddrState'])->toBe('');
});
