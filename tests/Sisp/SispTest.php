<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\TransactionData;
use Akira\Sisp\Actions\GenerateFingerprintAction;

it('builds request payload via facade with deterministic fingerprint', function (): void {
    $data = PaymentRequestData::from([
        'amount' => 10.5,
        'merchantRef' => 'MR-X',
        'merchantSession' => 'MS-Y',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    $req = Sisp::buildRequestPayload($data);
    $arr = $req->toArray();

    $expectedFingerprint = resolve(GenerateFingerprintAction::class)->handle([
        'timeStamp' => '2024-01-01 00:00:00',
        'amount' => 10.5,
        'merchantRef' => 'MR-X',
        'merchantSession' => 'MS-Y',
        'posID' => config('sisp.posID'),
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    expect($arr['fingerprint'])->toBe($expectedFingerprint)
        ->and($arr['merchantRef'])->toBe('MR-X')
        ->and($arr['merchantSession'])->toBe('MS-Y');
});

it('generates sandbox payload and validates callback', function (): void {
    $data = PaymentRequestData::from([
        'amount' => 50.0,
        'merchantRef' => 'MR-CB',
        'merchantSession' => 'MS-CB',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    $payload = Sisp::generateSandboxPayload($data, 'success');

    expect($payload->fingerprint)->not->toBe('')
        ->and(Sisp::validateCallback($payload))->toBeTrue();
});

it('stores a transaction and lists it via facade', function (): void {
    $t = Sisp::storeTransaction(TransactionData::from([
        'merchantRef' => 'MR-S',
        'merchantSession' => 'MS-S',
        'amount' => 99.0,
        'currency' => '132',
        'transactionCode' => '1',
        'locale' => 'pt',
        'payload' => ['foo' => 'bar'],
    ]));

    expect($t)->toBeInstanceOf(Transaction::class)
        ->and($t->exists)->toBeTrue();

    $all = Sisp::getTransactions();
    expect($all->contains(fn ($tr) => $tr->id === $t->id))->toBeTrue();
});

it('handles payment callback and updates status', function (): void {
    // Create a matching transaction in DB
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CB2',
        'merchant_session' => 'MS-CB2',
        'amount' => 20.0,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $data = PaymentRequestData::from([
        'amount' => 20.0,
        'merchantRef' => 'MR-CB2',
        'merchantSession' => 'MS-CB2',
        'timeStamp' => '2024-01-03 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    $payload = Sisp::generateSandboxPayload($data, 'success');

    $updated = Sisp::handlePaymentCallback($payload);

    expect($updated->status->value)->toBe('completed');
});

