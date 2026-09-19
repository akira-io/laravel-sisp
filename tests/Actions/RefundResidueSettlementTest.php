<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('refunds the invoice when a residue below a centavo settles the refund', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($transaction);
    $invoice->update(['status' => InvoiceStatus::paid->value]);
    $action = resolve(RefundTransactionAction::class);

    $transaction = $action->handle($transaction, 33.333333);
    $transaction = $action->handle($transaction, 33.333333);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::paid);

    $action->handle($transaction, 33.333333);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::refunded);
});

it('offers nothing to refund on a transaction already left with a residue below a centavo', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
        'payload' => ['refunds' => [['amount' => 99.999, 'reason' => 'earlier', 'request' => []]]],
    ]);

    expect($transaction->refundableAmount())->toBe(0.0)
        ->and(resolve(RefundTransactionAction::class)->refundableAmount($transaction))->toBe(0.0);
});
