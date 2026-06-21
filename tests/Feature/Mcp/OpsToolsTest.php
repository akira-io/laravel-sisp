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
use Illuminate\Support\Facades\Http;

it('builds a payment request without persisting a transaction', function (): void {
    SispOpsServer::tool(BuildPaymentRequestTool::class, ['amount' => 1500.0])
        ->assertOk()
        ->assertSee('payment_request');

    expect(Transaction::query()->count())->toBe(0);
});

it('rejects a non-positive amount', function (): void {
    SispOpsServer::tool(BuildPaymentRequestTool::class, ['amount' => 0])
        ->assertHasErrors();
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
        ->assertHasErrors();
});

it('lists transactions filtered by status', function (): void {
    Transaction::factory()->completed()->create();
    Transaction::factory()->pending()->create();

    SispOpsServer::tool(ListTransactionsTool::class, ['status' => 'completed'])
        ->assertOk()
        ->assertSee('completed');
});

it('rejects an invalid status filter', function (): void {
    SispOpsServer::tool(ListTransactionsTool::class, ['status' => 'bogus'])
        ->assertHasErrors();
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

it('refunds a completed transaction in full', function (): void {
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => (string) $transaction->id])
        ->assertOk()
        ->assertSee('refunded');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::refunded);
});

it('refunds a completed transaction partially', function (): void {
    $transaction = Transaction::factory()->completed()->create([
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispOpsServer::tool(RefundTransactionTool::class, [
        'transaction' => (string) $transaction->id,
        'amount' => 40.0,
        'reason' => 'partial_return',
    ])->assertOk();
});

it('fails to refund a pending transaction', function (): void {
    $transaction = Transaction::factory()->pending()->create();

    SispOpsServer::tool(RefundTransactionTool::class, ['transaction' => (string) $transaction->id])
        ->assertHasErrors();
});

it('cancels a pending transaction', function (): void {
    $transaction = Transaction::factory()->pending()->create();

    SispOpsServer::tool(CancelTransactionTool::class, ['transaction' => (string) $transaction->id])
        ->assertOk()
        ->assertSee('cancelled');

    expect($transaction->fresh()->status)->toBe(TransactionStatus::cancelled);
});

it('fails to cancel a completed transaction', function (): void {
    $transaction = Transaction::factory()->completed()->create();

    SispOpsServer::tool(CancelTransactionTool::class, ['transaction' => (string) $transaction->id])
        ->assertHasErrors();
});
