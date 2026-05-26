<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildRefundRequestAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('builds total reversal requests with refund fingerprint version 2', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 1500.0,
        'currency' => '132',
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $request = resolve(BuildRefundRequestAction::class)->total($transaction);
    $payload = $request->toArray();

    expect($payload['transactionCode'])->toBe('4')
        ->and($payload['fingerprintversion'])->toBe('2')
        ->and($payload['reversal'])->toBe('R')
        ->and($payload['clearingPeriod'])->toBe('5')
        ->and($payload['transactionID'])->toBe('123')
        ->and($payload['fingerprint'])->not->toBe('');
});

it('builds partial refund requests with transaction code 8', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 1500.0,
        'currency' => '132',
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $payload = resolve(BuildRefundRequestAction::class)->partial($transaction, 500.0)->toArray();

    expect($payload['transactionCode'])->toBe('8')
        ->and($payload['amount'])->toBe(500.0)
        ->and($payload['reversal'])->toBe('R');
});

it('builds refund history requests with transaction code 9 and zero amount', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 1500.0,
        'currency' => '132',
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $payload = resolve(BuildRefundRequestAction::class)->history($transaction)->toArray();

    expect($payload['transactionCode'])->toBe('9')
        ->and($payload['amount'])->toBe(0.0)
        ->and($payload['reversal'])->toBe('R');
});

it('requires original clearing period and transaction id', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 1500.0,
    ]);

    expect(fn () => resolve(BuildRefundRequestAction::class)->total($transaction))
        ->toThrow(LogicException::class, 'SISP refund requires original clearingPeriod.');
});
