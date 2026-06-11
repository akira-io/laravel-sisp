<?php

declare(strict_types=1);

use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Drivers\ProductionDriver;
use Akira\Sisp\Drivers\SandboxDriver;
use Akira\Sisp\Drivers\SispManager;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;
use Illuminate\Support\Facades\Http;

it('resolves the production driver when sandbox is disabled', function (): void {
    config()->set('sisp.sandbox', false);

    $driver = resolve(SispManager::class)->driver();

    expect($driver)->toBeInstanceOf(ProductionDriver::class)
        ->and($driver->name())->toBe('production')
        ->and($driver->paymentEndpoint())->toBe('https://test.sisp.example.com');
});

it('resolves the sandbox driver when sandbox is enabled', function (): void {
    config()->set('sisp.sandbox', true);

    $driver = resolve(SispManager::class)->driver();

    expect($driver)->toBeInstanceOf(SandboxDriver::class)
        ->and($driver->name())->toBe('sandbox')
        ->and($driver->paymentEndpoint())->toBe(route('sisp.sandbox'));
});

it('honours an explicit driver from config over the sandbox flag', function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.driver', 'production');

    expect(resolve(SispManager::class)->driver())->toBeInstanceOf(ProductionDriver::class);
});

it('exposes the driver through the facade and the container contract', function (): void {
    config()->set('sisp.sandbox', false);

    expect(Sisp::driver())->toBeInstanceOf(ProductionDriver::class)
        ->and(Sisp::driver('sandbox'))->toBeInstanceOf(SandboxDriver::class)
        ->and(resolve(SispDriver::class))->toBeInstanceOf(ProductionDriver::class);
});

it('supports registering custom drivers via extend', function (): void {
    $custom = new class implements SispDriver
    {
        public function name(): string
        {
            return 'custom';
        }

        public function paymentEndpoint(): string
        {
            return 'https://custom.gateway.example.com';
        }

        public function queryTransactionStatus(Transaction|string $transaction): TransactionStatusResponse
        {
            return TransactionStatusResponse::from([
                'result' => true,
                'transactionSuccess' => true,
                'transactionStatusDescription' => 'C-SUCESSO',
                'msg' => 'ok',
            ]);
        }
    };

    $manager = resolve(SispManager::class);
    $manager->extend('custom', fn (): SispDriver => $custom);

    config()->set('sisp.driver', 'custom');

    expect($manager->driver()->name())->toBe('custom')
        ->and($manager->driver()->paymentEndpoint())->toBe('https://custom.gateway.example.com');
});

it('queries the transaction status through the driver', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $response = resolve(SispManager::class)->driver()->queryTransactionStatus('MR-DRIVER');

    expect($response->paymentStatus()->value)->toBe('completed');
});
