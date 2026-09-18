<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Mcp\Servers\SispOpsServer;
use Akira\Sisp\Mcp\Tools\Ops\BuildPaymentRequestTool;
use Akira\Sisp\Mcp\Tools\Ops\CancelTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\GetTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\ListTransactionsTool;
use Akira\Sisp\Mcp\Tools\Ops\QueryTransactionStatusTool;
use Akira\Sisp\Mcp\Tools\Ops\ReconcileTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\RefundTransactionTool;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('builds a payment request without persisting a transaction', function (): void {
    SispOpsServer::tool(BuildPaymentRequestTool::class, ['amount' => 1500.0])
        ->assertOk()
        ->assertSee('payment_request');

    expect(Transaction::query()->count())->toBe(0);
});

it('rejects a non-positive amount', function (): void {
    SispOpsServer::tool(BuildPaymentRequestTool::class, ['amount' => 0])
        ->assertHasErrors(['amount']);
});

it('fetches a transaction by merchant reference', function (): void {
    $transaction = Transaction::factory()->create(['merchant_ref' => 'REF-123']);

    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => 'REF-123'])
        ->assertOk()
        ->assertSee('REF-123');
});

it('fetches a transaction by id', function (): void {
    $transaction = Transaction::factory()->create();

    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => (string) $transaction->id])
        ->assertOk()
        ->assertSee($transaction->merchant_ref);
});

it('errors when a transaction is not found', function (): void {
    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => 'missing'])
        ->assertHasErrors(['No transaction found for "missing".']);
});

it('lists transactions filtered by status', function (): void {
    Transaction::factory()->completed()->create(['merchant_ref' => 'REF-DONE']);
    Transaction::factory()->pending()->create(['merchant_ref' => 'REF-WAITING']);

    SispOpsServer::tool(ListTransactionsTool::class, ['status' => 'completed'])
        ->assertOk()
        ->assertSee(['REF-DONE', '"count":1'])
        ->assertDontSee('REF-WAITING');
});

it('rejects an invalid status filter', function (): void {
    SispOpsServer::tool(ListTransactionsTool::class, ['status' => 'bogus'])
        ->assertHasErrors(['Invalid status']);
});

it('queries the live transaction status', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake(['*' => Http::response([
        'result' => true,
        'transactionSuccess' => true,
        'transactionStatusDescription' => 'C-SUCESSO',
        'msg' => 'Approved',
    ])]);

    $transaction = Transaction::factory()->create(['merchant_ref' => 'REF-Q']);

    SispOpsServer::tool(QueryTransactionStatusTool::class, ['transaction' => 'REF-Q'])
        ->assertOk()
        ->assertSee('completed');
});

it('reconciles a pending transaction', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake(['*' => Http::response([
        'result' => true,
        'transactionSuccess' => true,
        'transactionStatusDescription' => 'C-SUCESSO',
        'msg' => 'Approved',
    ])]);

    $transaction = Transaction::factory()->create(['status' => 'pending', 'merchant_ref' => 'REF-R']);

    SispOpsServer::tool(ReconcileTransactionTool::class, ['transaction' => 'REF-R'])
        ->assertOk()
        ->assertSee('completed');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::completed);
});

it('refuses a numeric identifier that matches two transactions', function (): void {
    $byId = Transaction::factory()->pending()->create();
    $byRef = Transaction::factory()->pending()->create(['merchant_ref' => (string) $byId->id]);

    SispOpsServer::tool(CancelTransactionTool::class, ['transaction' => (string) $byId->id])
        ->assertHasErrors(['Prefix it with "id:" or "ref:"']);

    expect($byId->fresh()->status)->toBe(TransactionStatus::pending)
        ->and($byRef->fresh()->status)->toBe(TransactionStatus::pending);
});

it('resolves an explicit id or merchant reference prefix', function (): void {
    $byId = Transaction::factory()->create(['merchant_ref' => 'REF-BY-ID-PREFIX']);
    Transaction::factory()->create(['merchant_ref' => (string) $byId->id]);

    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => 'id:'.$byId->id])
        ->assertOk()
        ->assertSee('REF-BY-ID-PREFIX');

    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => 'ref:'.$byId->id])
        ->assertOk()
        ->assertSee('"merchant_ref":"'.$byId->id.'"');
});

it('queries the gateway with the stored merchant reference when given an id', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'msg' => 'Approved'])]);

    $transaction = Transaction::factory()->create(['merchant_ref' => 'REF-BY-ID']);

    SispOpsServer::tool(QueryTransactionStatusTool::class, ['transaction' => (string) $transaction->id])
        ->assertOk();

    Http::assertSent(fn (Request $request): bool => $request['merchantRef'] === 'REF-BY-ID');
});

it('does not query the gateway for an unknown transaction', function (): void {
    Http::fake();

    SispOpsServer::tool(QueryTransactionStatusTool::class, ['transaction' => 'R-SOMEONE-ELSE'])
        ->assertHasErrors(['No transaction found']);

    Http::assertNothingSent();
});

it('keeps credentials and personal data out of the transaction summary', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'REF-PII',
        'merchant_session' => 'SESSION-SECRET',
        'customer_email' => 'victim@example.com',
        'customer_phone' => '+2389990000',
        'customer_address' => 'Rua Secreta 1',
        'payload' => ['merchantRespPan' => '4111111111111111'],
        'callback_raw_payload' => ['purchaseRequest' => '3DS-DATA'],
    ]);

    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => 'REF-PII'])
        ->assertOk()
        ->assertSee('v***@example.com')
        ->assertDontSee(['SESSION-SECRET', 'victim@', '+2389990000', 'Rua Secreta', '4111111111111111', '3DS-DATA']);
});

it('reports the refusal reason sisp sent, capped in length', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'REF-REFUSED',
        'status' => 'failed',
        'error_code' => '3',
        'error_message' => 'Saldo do cartao insuficiente'.str_repeat('.', 400),
    ]);

    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => 'REF-REFUSED'])
        ->assertOk()
        ->assertSee(['Saldo do cartao insuficiente', '"error_code":"3"'])
        ->assertDontSee(str_repeat('.', 256));
});

it('builds a payment request with the customer fields it is given', function (): void {
    SispOpsServer::tool(BuildPaymentRequestTool::class, [
        'amount' => 1500.0,
        'locale' => 'pt',
        'customerEmail' => 'buyer@example.com',
        'customerCountry' => 'CV',
        'customerCity' => 'Praia',
        'customerAddress' => 'Rua 1',
        'customerPhone' => '',
    ])->assertOk()->assertSee(['payment_request', 'Preview only'])->assertDontSee('"fingerprint"');
});

it('previews the 3-d secure purchase request without a fingerprint', function (): void {
    config()->set('sisp.is_3dsec', '1');

    SispOpsServer::tool(BuildPaymentRequestTool::class, [
        'amount' => 1500.0,
        'customerEmail' => 'buyer@example.com',
        'customerCountry' => 'CV',
        'customerCity' => 'Praia',
        'customerAddress' => 'Rua 1',
        'customerPostalCode' => '7600',
        'customerPhone' => '+2389990000',
    ])->assertOk()->assertSee('purchaseRequest')->assertDontSee('"fingerprint"');
});

it('explains why a 3-d secure payment request cannot be built', function (): void {
    config()->set('sisp.is_3dsec', '1');

    SispOpsServer::tool(BuildPaymentRequestTool::class, ['amount' => 1500.0])
        ->assertHasErrors(['Could not build payment request']);
});

it('lists transactions inside a date window', function (): void {
    Transaction::factory()->create(['merchant_ref' => 'REF-OLD', 'created_at' => now()->subDays(10)]);
    Transaction::factory()->create(['merchant_ref' => 'REF-NEW', 'created_at' => now()->subDay()]);

    SispOpsServer::tool(ListTransactionsTool::class, [
        'from' => now()->subDays(2)->toIso8601String(),
        'to' => now()->toIso8601String(),
        'limit' => 5,
    ])->assertOk()->assertSee('REF-NEW')->assertDontSee('REF-OLD');
});

it('includes the whole day when the upper bound is a bare date', function (): void {
    Transaction::factory()->create(['merchant_ref' => 'REF-EVENING', 'created_at' => '2026-09-17 21:30:00']);
    Transaction::factory()->create(['merchant_ref' => 'REF-NEXT-DAY', 'created_at' => '2026-09-18 00:00:01']);

    SispOpsServer::tool(ListTransactionsTool::class, ['from' => '2026-09-17', 'to' => '2026-09-17'])
        ->assertOk()
        ->assertSee('REF-EVENING')
        ->assertDontSee('REF-NEXT-DAY');
});

it('converts a bound with an offset to the application timezone', function (): void {
    config()->set('app.timezone', 'UTC');
    Transaction::factory()->create(['merchant_ref' => 'REF-BEFORE', 'created_at' => '2026-09-17 08:59:00']);
    Transaction::factory()->create(['merchant_ref' => 'REF-AFTER', 'created_at' => '2026-09-17 09:30:00']);

    SispOpsServer::tool(ListTransactionsTool::class, ['from' => '2026-09-17T10:00:00+01:00'])
        ->assertOk()
        ->assertSee('REF-AFTER')
        ->assertDontSee('REF-BEFORE');
});

it('reports a missing transaction on every transaction tool', function (string $tool, array $arguments): void {
    Http::fake();

    SispOpsServer::tool($tool, ['transaction' => 'REF-MISSING', ...$arguments])
        ->assertHasErrors(['No transaction found for "REF-MISSING".']);

    Http::assertNothingSent();
})->with([
    'get' => [GetTransactionTool::class, []],
    'query' => [QueryTransactionStatusTool::class, []],
    'reconcile' => [ReconcileTransactionTool::class, []],
    'refund' => [RefundTransactionTool::class, ['amount' => 10]],
    'cancel' => [CancelTransactionTool::class, []],
]);

it('fully masks a customer email it cannot parse', function (): void {
    Transaction::factory()->create(['merchant_ref' => 'REF-ODD', 'customer_email' => 'not-an-email']);

    SispOpsServer::tool(GetTransactionTool::class, ['transaction' => 'REF-ODD'])
        ->assertOk()
        ->assertSee('"customer_email":"***"')
        ->assertDontSee('not-an-email');
});
