<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\DB;

it('leaves the invoice alone when the transaction moved on before the invoice update', function (): void {
    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
        'amount' => 100.0,
    ]);

    $invoice = $transaction->invoice()->create([
        'invoice_number' => 'INV-RECONCILE-RACE',
        'invoice_date' => now()->toDateString(),
        'status' => InvoiceStatus::refunded->value,
    ]);

    DB::table(config('sisp.tables.transactions'))
        ->where('id', $transaction->id)
        ->update(['status' => TransactionStatus::refunded->value]);

    $guard = new ReflectionMethod(ReconcileTransactionStatusAction::class, 'updateInvoiceWhenStatusStillHolds');

    $guard->invoke(resolve(ReconcileTransactionStatusAction::class), $transaction, TransactionStatus::completed);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::refunded);
});
