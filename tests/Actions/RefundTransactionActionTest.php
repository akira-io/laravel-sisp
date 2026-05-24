<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('refunds a completed transaction for the full original amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 100.0, 'customer_request');

    expect($updated->status->value)->toBe('refunded')
        ->and($updated->amount)->toBe(100.0)
        ->and($updated->merchant_response)->toBe('customer_request::100');
});

it('does not allow partial refund amounts', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 50.0))
        ->toThrow(LogicException::class, 'SISP only supports full-amount refunds')
        ->and($t->refresh()->status)->toBe(TransactionStatus::completed)
        ->and($t->amount)->toBe(100.0);
});

it('does not allow refund amounts above the transaction amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 10.0,
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 15.0))
        ->toThrow(LogicException::class, 'SISP only supports full-amount refunds');
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
