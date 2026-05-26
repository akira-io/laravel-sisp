<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('refunds a completed transaction for the full original amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 100.0, 'customer_request');

    expect($updated->status->value)->toBe('refunded')
        ->and($updated->amount)->toBe(100.0)
        ->and($updated->merchant_response)->toBe('customer_request::100');
});

it('compares decimal refund amounts using canonical thousandths', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 8.03,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 8.03, 'decimal_refund');

    expect($updated->status->value)->toBe('refunded')
        ->and($updated->amount)->toBe(8.03)
        ->and($updated->amount_cents)->toBe(803);
});

it('allows partial refund amounts and keeps the transaction completed', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 50.0, 'partial_request');

    expect($updated->status)->toBe(TransactionStatus::completed)
        ->and($updated->payload['refunds'][0]['request']['transactionCode'])->toBe('8')
        ->and($updated->payload['refunds'][0]['amount'])->toBe(50);
});

it('records refund updates with the refund log source', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    resolve(RefundTransactionAction::class)->handle($t, 50.0, 'partial_request');

    $log = $t->logs()->sole();

    expect($log->source)->toBe('refund')
        ->and($log->changed_attributes)->toContain('payload')
        ->and($log->new_values['payload']['refunds'][0]['reason'])->toBe('partial_request');
});

it('does not allow refund amounts above the transaction amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 10.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 15.0))
        ->toThrow(LogicException::class, 'Refund amount (15) exceeds refundable balance.');
});

it('does not allow refund for non-completed status', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::pending->value,
        'amount' => 10.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 5.0))
        ->toThrow(LogicException::class);
});

it('does not allow zero or negative refund', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 10.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 0.0))
        ->toThrow(LogicException::class);
});

it('does not allow refunds above the remaining local balance', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
        'payload' => [
            'refunds' => [
                ['amount' => 75.0],
            ],
        ],
    ]);

    expect(fn () => resolve(RefundTransactionAction::class)->handle($t, 50.0))
        ->toThrow(LogicException::class, 'Refund amount (50) exceeds refundable balance.');
});
