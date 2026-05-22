<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GenerateFingerprintAction;

beforeEach(function (): void {
    $this->action = resolve(GenerateFingerprintAction::class);
});

it('generates the known SISP request fingerprint vector', function (): void {
    $data = [
        'timeStamp' => '2024-01-15 14:30:00',
        'amount' => 100.50,
        'merchantRef' => 'test-ref-123',
        'merchantSession' => 'test-session-456',
        'posID' => 'POS-001',
        'currency' => 'AOA',
        'transactionCode' => 'PURCHASE',
    ];

    $fingerprint = $this->action->handle($data);

    expect($fingerprint)->toBe('xoYJjgMu1BZN/pZHxIj2GL9gyulZjByJ/moOMc6iDd/N962z6GYHGqZfnIQKoxfxpUiM79NvA6WrasgecGAqJg==');
});

it('amount is converted to integer milliseconds', function (): void {
    $data1 = [
        'timeStamp' => '2024-01-15 14:30:00',
        'amount' => 100.50,
        'merchantRef' => 'test-ref-123',
        'merchantSession' => 'test-session-456',
        'posID' => 'POS-001',
        'currency' => 'AOA',
        'transactionCode' => 'PURCHASE',
    ];

    $data2 = [
        'timeStamp' => '2024-01-15 14:30:00',
        'amount' => 100.50,
        'merchantRef' => 'test-ref-123',
        'merchantSession' => 'test-session-456',
        'posID' => 'POS-001',
        'currency' => 'AOA',
        'transactionCode' => 'PURCHASE',
    ];

    $fingerprint1 = $this->action->handle($data1);
    $fingerprint2 = $this->action->handle($data2);

    expect($fingerprint1)->toBe($fingerprint2);
});

it('different amounts generate different fingerprints', function (): void {
    $data1 = [
        'timeStamp' => '2024-01-15 14:30:00',
        'amount' => 100.50,
        'merchantRef' => 'test-ref-123',
        'merchantSession' => 'test-session-456',
        'posID' => 'POS-001',
        'currency' => 'AOA',
        'transactionCode' => 'PURCHASE',
    ];

    $data2 = [
        'timeStamp' => '2024-01-15 14:30:00',
        'amount' => 200.75,
        'merchantRef' => 'test-ref-123',
        'merchantSession' => 'test-session-456',
        'posID' => 'POS-001',
        'currency' => 'AOA',
        'transactionCode' => 'PURCHASE',
    ];

    $fingerprint1 = $this->action->handle($data1);
    $fingerprint2 = $this->action->handle($data2);

    expect($fingerprint1)->not->toBe($fingerprint2);
});
