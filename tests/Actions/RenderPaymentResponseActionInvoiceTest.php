<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;

it('renderInertia includes invoice data when present', function (): void {
    $t = Transaction::factory()->create();

    $invoice = Invoice::query()->create([
        'transaction_id' => $t->id,
        'invoice_number' => 'INV-000001',
        'invoice_date' => now()->toDateString(),
        'status' => 'pending',
        'pdf_path' => 'invoices/test.pdf',
    ]);

    $resp = resolve(RenderPaymentResponseAction::class)->renderInertia($t, []);
    expect($resp)->toBeInstanceOf(Inertia\Response::class);
});
