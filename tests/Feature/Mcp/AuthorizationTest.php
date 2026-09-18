<?php

declare(strict_types=1);

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Mcp\Servers\SispWebOpsServer;
use Akira\Sisp\Mcp\Tools\Ops\BuildPaymentRequestTool;
use Akira\Sisp\Mcp\Tools\Ops\CancelTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\GetTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\ListTransactionsTool;
use Akira\Sisp\Mcp\Tools\Ops\QueryTransactionStatusTool;
use Akira\Sisp\Mcp\Tools\Ops\ReconcileTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\RefundTransactionTool;
use Akira\Sisp\Models\Transaction;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Facades\Mcp;

beforeEach(function (): void {
    config()->set('sisp.mcp.web.expose_destructive', true);
});

function mcpOperator(): GenericUser
{
    return new GenericUser(['id' => 7]);
}

function allowMcpOperations(string ...$operations): void
{
    Gate::define('sisp-mcp', fn (GenericUser $user, string $operation, ?Transaction $transaction = null): bool => in_array($operation, $operations, true));
}

it('denies every web tool when the host has not defined the ability', function (string $tool, array $arguments): void {
    $transaction = Transaction::factory()->completed()->create(['merchant_ref' => 'REF-DENY', 'amount' => 100.0]);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool($tool, $arguments)
        ->assertHasErrors(['Not authorized']);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::completed);
})->with([
    'get' => [GetTransactionTool::class, ['transaction' => 'REF-DENY']],
    'list' => [ListTransactionsTool::class, []],
    'query' => [QueryTransactionStatusTool::class, ['transaction' => 'REF-DENY']],
    'reconcile' => [ReconcileTransactionTool::class, ['transaction' => 'REF-DENY']],
    'build' => [BuildPaymentRequestTool::class, ['amount' => 100]],
    'refund' => [RefundTransactionTool::class, ['transaction' => 'REF-DENY', 'amount' => 100]],
    'cancel' => [CancelTransactionTool::class, ['transaction' => 'REF-DENY']],
]);

it('passes the operation and transaction to the host ability', function (): void {
    $transaction = Transaction::factory()->create(['merchant_ref' => 'REF-SEEN']);
    $seen = [];

    Gate::define('sisp-mcp', function (GenericUser $user, string $operation, ?Transaction $subject = null) use (&$seen): bool {
        $seen[] = [$operation, $subject?->id];

        return true;
    });

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(GetTransactionTool::class, ['transaction' => 'REF-SEEN'])
        ->assertOk();

    expect($seen)->toBe([['view', $transaction->id]]);
});

it('lets an operator read transactions once the ability allows it', function (): void {
    allowMcpOperations('view', 'list');
    Transaction::factory()->create(['merchant_ref' => 'REF-OK']);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(GetTransactionTool::class, ['transaction' => 'REF-OK'])
        ->assertOk()
        ->assertSee('REF-OK');

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(ListTransactionsTool::class, [])
        ->assertOk()
        ->assertSee('REF-OK');
});

it('scopes each operation separately', function (): void {
    allowMcpOperations('view');
    $transaction = Transaction::factory()->pending()->create(['merchant_ref' => 'REF-SCOPE']);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(CancelTransactionTool::class, ['transaction' => 'REF-SCOPE'])
        ->assertHasErrors(['Not authorized to cancel']);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::pending);
});

it('requires the refund policy on top of the mcp ability, like the http refund route', function (): void {
    allowMcpOperations('refund');
    $transaction = Transaction::factory()->completed()->create([
        'merchant_ref' => 'REF-POLICY',
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(RefundTransactionTool::class, ['transaction' => 'REF-POLICY', 'amount' => 100])
        ->assertHasErrors(['Not authorized to refund transactions.']);

    expect($transaction->fresh()->status)->toBe(TransactionStatus::completed);

    Gate::define('refund', fn (GenericUser $user, Transaction $subject): bool => true);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(RefundTransactionTool::class, ['transaction' => 'REF-POLICY', 'amount' => 100])
        ->assertOk();

    expect($transaction->fresh()->status)->toBe(TransactionStatus::refunded);
});

it('denies unauthenticated calls over http even when the route has no auth middleware', function (): void {
    config()->set('sisp.mcp.local', false);
    config()->set('sisp.mcp.web.enabled', true);
    config()->set('sisp.mcp.web.path', '/sisp/mcp-open');
    config()->set('sisp.mcp.web.middleware', []);
    require dirname(__DIR__, 3).'/routes/ai.php';
    resolve(Illuminate\Routing\Router::class)->getRoutes()->refreshNameLookups();

    expect(Mcp::getWebServer('sisp/mcp-open'))->not->toBeNull();

    Transaction::factory()->create(['merchant_ref' => 'REF-HTTP', 'customer_email' => 'victim@example.com']);

    $response = $this->postJson('/sisp/mcp-open', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => ['name' => 'list-transactions-tool', 'arguments' => []],
    ]);

    $response->assertOk();

    expect($response->getContent())
        ->toContain('Not authorized')
        ->not->toContain('REF-HTTP')
        ->not->toContain('victim');
});
