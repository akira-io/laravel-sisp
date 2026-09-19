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

it('renderInertia keeps the merchant session on the full page', function (): void {
    $transaction = Transaction::factory()->create(['merchant_session' => 'MS-OWNER']);

    $request = request();
    $request->headers->set(Header::INERTIA, 'true');
    $data = resolve(RenderPaymentResponseAction::class)->renderInertia($transaction, [])->toResponse($request)->getData(true);

    expect($data['props']['transaction']['merchant_session'])->toBe('MS-OWNER');
});

it('renderInertia leaves the merchant session, the invoice and the retry link out of the reduced page', function (): void {
    $transaction = Transaction::factory()->create(['merchant_session' => 'MS-SECRET', 'status' => 'failed']);
    Invoice::query()->create([
        'transaction_id' => $transaction->id,
        'invoice_number' => 'INV-REDUCED-1',
        'invoice_date' => now(),
        'status' => 'pending',
    ]);

    $request = request();
    $request->headers->set(Header::INERTIA, 'true');
    $data = resolve(RenderPaymentResponseAction::class)->renderInertia($transaction, [], restricted: true)->toResponse($request)->getData(true);

    expect($data['props']['transaction'])->not->toHaveKey('merchant_session')
        ->and($data['props']['transaction']['merchant_ref'])->toBe($transaction->merchant_ref)
        ->and($data['props']['invoice'])->toBeNull()
        ->and($data['props']['allowRetry'])->toBeFalse()
        ->and($data['props']['retryUrl'])->toBeNull();
});
