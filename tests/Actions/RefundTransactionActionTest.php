<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\InvoiceStatus;
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
        ->and($log->new_values['payload'])->toBe('[redacted]');
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

it('marks the invoice as refunded when the transaction is fully refunded', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($t);
    $invoice->update(['status' => InvoiceStatus::paid->value]);

    resolve(RefundTransactionAction::class)->handle($t, 100.0, 'customer_request');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::refunded);
});

it('leaves the invoice untouched when only part of the transaction is refunded', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($t);
    $invoice->update(['status' => InvoiceStatus::issued->value]);

    resolve(RefundTransactionAction::class)->handle($t, 40.0, 'partial_request');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::issued)
        ->and($invoice->pdf_path)->toBeNull();
});

it('rereads the refunded balance so a stale instance cannot refund twice', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $stale = Transaction::query()->whereKey($t->getKey())->sole();

    resolve(RefundTransactionAction::class)->handle($t, 60.0, 'partial_request');

    expect(fn () => resolve(RefundTransactionAction::class)->handle($stale, 60.0, 'partial_request'))
        ->toThrow(LogicException::class, 'Refund amount (60) exceeds refundable balance.');
});

it('marks the invoice as refunded once successive partial refunds cover the amount', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($t);
    $invoice->update(['status' => InvoiceStatus::paid->value]);

    $action = resolve(RefundTransactionAction::class);
    $action->handle($t, 60.0, 'partial_request');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::paid);

    $action->handle($t->refresh(), 40.0, 'partial_request');

    expect($t->refresh()->status)->toBe(TransactionStatus::refunded)
        ->and($invoice->refresh()->status)->toBe(InvoiceStatus::refunded);
});

it('refunds a transaction that has no invoice', function (): void {
    $t = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    $updated = resolve(RefundTransactionAction::class)->handle($t, 100.0, 'customer_request');

    expect($updated->status)->toBe(TransactionStatus::refunded)
        ->and($updated->invoice)->toBeNull();
});
