<?php

declare(strict_types=1);

use Akira\Sisp\Actions\RenderPaymentResponseAction;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Inertia\Support\Header;

it('renderInertia includes invoice data when present', function (): void {
    $t = Transaction::factory()->create();

    Invoice::query()->create([
        'transaction_id' => $t->id,
        'invoice_number' => 'INV-000001',
        'invoice_date' => '2026-05-23',
        'status' => 'pending',
        'pdf_path' => 'invoices/test.pdf',
    ]);

    $resp = resolve(RenderPaymentResponseAction::class)->renderInertia($t, []);
    $request = request();
    $request->headers->set(Header::INERTIA, 'true');
    $data = $resp->toResponse($request)->getData(true);

    expect($resp)->toBeInstanceOf(Inertia\Response::class)
        ->and($data['props']['invoice'])->toMatchArray([
            'invoice_number' => 'INV-000001',
            'invoice_date' => '2026-05-23',
            'status' => 'pending',
            'pdf_path' => 'invoices/test.pdf',
        ])
        ->and($data['props']['invoice']['pdf_url'])->toContain('invoices/test.pdf');
});
