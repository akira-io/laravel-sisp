<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\UpdateInvoiceStatusAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;

it('updates invoice status based on transaction status', function (): void {
    $t = Transaction::factory()->create(['status' => 'pending']);

    $invoice = resolve(GenerateInvoiceAction::class)->handle($t);

    $action = resolve(UpdateInvoiceStatusAction::class);

    $action->handle($invoice->transaction, TransactionStatus::pending);
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::pending);

    $action->handle($invoice->transaction, TransactionStatus::failed);
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::cancelled);
});
