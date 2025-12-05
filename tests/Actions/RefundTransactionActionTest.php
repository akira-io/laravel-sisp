<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('refunds a completed transaction and updates amount/status', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 25.5, 'partial_refund');

    expect($updated->status->value)->toBe('refunded')
        ->and($updated->amount)->toBe(74.5)
        ->and($updated->merchant_response)->toBe('partial_refund::25.5');
});

it('does not allow refund exceeding amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 10.0,
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 15.0))
        ->toThrow(LogicException::class);
});

it('does not allow refund for non-completed status', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::pending->value,
        'amount' => 10.0,
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 5.0))
        ->toThrow(LogicException::class);
});

it('does not allow zero or negative refund', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 10.0,
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 0.0))
        ->toThrow(LogicException::class);
});
