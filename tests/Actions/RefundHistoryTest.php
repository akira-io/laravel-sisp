<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;

it('sums the refunds recorded in the table', function (): void {
    $transaction = Transaction::factory()->create(['amount' => 1000.0]);

    Refund::query()->create([
        'transaction_id' => $transaction->id,
        'amount' => 250.0,
        'reason' => 'partial',
        'request' => ['amount' => 250.0],
    ]);

    expect($transaction->refresh()->refunds)->toHaveCount(1)
        ->and((float) $transaction->refunds->sum('amount'))->toBe(250.0);
});

it('falls back to the legacy payload when the table has no rows', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 1000.0,
        'payload' => [
            'refunds' => [
                ['amount' => 400.0, 'reason' => 'legacy', 'request' => []],
            ],
        ],
    ]);

    $refundable = resolve(RefundTransactionAction::class)->refundableAmount($transaction);

    expect($refundable)->toBe(600.0);
});

it('belongs to the transaction it refunds', function (): void {
    $transaction = Transaction::factory()->create(['amount' => 1000.0]);

    $refund = Refund::query()->create([
        'transaction_id' => $transaction->id,
        'amount' => 100.0,
        'reason' => 'partial',
        'request' => [],
    ]);

    expect($refund->transaction->id)->toBe($transaction->id);
});

it('prefers the table over the legacy payload once a row exists', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 1000.0,
        'payload' => [
            'refunds' => [
                ['amount' => 400.0, 'reason' => 'legacy', 'request' => []],
            ],
        ],
    ]);

    Refund::query()->create([
        'transaction_id' => $transaction->id,
        'amount' => 400.0,
        'reason' => 'migrated',
        'request' => [],
    ]);

    expect(resolve(RefundTransactionAction::class)->refundableAmount($transaction->refresh()))
        ->toBe(600.0);
});
