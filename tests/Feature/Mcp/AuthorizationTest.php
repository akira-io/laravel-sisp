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
use Illuminate\Support\Facades\Http;
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
        ->assertHasErrors(['Not authorized to refund this transaction']);

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

it('asks the gate for the operation each tool performs', function (string $tool, string $operation, array $arguments): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'msg' => 'Approved'])]);
    allowMcpOperations($operation);
    Gate::define('refund', fn (GenericUser $user, Transaction $subject): bool => true);
    Transaction::factory()->completed()->create([
        'merchant_ref' => 'REF-OP',
        'amount' => 100.0,
        'transaction_id' => '123',
        'response_code' => '5',
    ]);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool($tool, $arguments)
        ->assertHasNoErrors();
})->with([
    'get' => [GetTransactionTool::class, 'view', ['transaction' => 'REF-OP']],
    'list' => [ListTransactionsTool::class, 'list', []],
    'query' => [QueryTransactionStatusTool::class, 'query', ['transaction' => 'REF-OP']],
    'reconcile' => [ReconcileTransactionTool::class, 'reconcile', ['transaction' => 'REF-OP']],
    'build' => [BuildPaymentRequestTool::class, 'build', ['amount' => 100]],
    'refund' => [RefundTransactionTool::class, 'refund', ['transaction' => 'REF-OP', 'amount' => 10]],
]);

it('asks the ability the host configured', function (): void {
    config()->set('sisp.mcp.web.ability', 'operate-payments');
    Gate::define('operate-payments', fn (GenericUser $user, string $operation, ?Transaction $transaction = null): bool => true);
    Transaction::factory()->create(['merchant_ref' => 'REF-CUSTOM']);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(GetTransactionTool::class, ['transaction' => 'REF-CUSTOM'])
        ->assertOk()
        ->assertSee('REF-CUSTOM');
});

it('does not tell a web caller whether a transaction exists', function (): void {
    allowMcpOperations('list');
    Transaction::factory()->create(['merchant_ref' => 'REF-EXISTS']);

    $message = 'Not authorized to view this transaction, or it does not exist.';

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(GetTransactionTool::class, ['transaction' => 'REF-EXISTS'])
        ->assertHasErrors([$message]);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(GetTransactionTool::class, ['transaction' => 'REF-MISSING'])
        ->assertHasErrors([$message]);
});

it('lists only the transactions the operator may view', function (): void {
    $visible = Transaction::factory()->create(['merchant_ref' => 'REF-MINE']);
    Transaction::factory()->create(['merchant_ref' => 'REF-THEIRS']);

    Gate::define('sisp-mcp', fn (GenericUser $user, string $operation, ?Transaction $transaction = null): bool => $operation === 'list' || $transaction?->is($visible) === true);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool(ListTransactionsTool::class, [])
        ->assertOk()
        ->assertSee(['REF-MINE', '"count":1'])
        ->assertDontSee('REF-THEIRS');
});

it('refuses destructive tools on the web server unless they are exposed', function (string $tool): void {
    config()->set('sisp.mcp.web.expose_destructive', false);
    allowMcpOperations('reconcile', 'refund', 'cancel');
    Http::fake();
    $transaction = Transaction::factory()->pending()->create(['merchant_ref' => 'REF-HIDDEN']);

    SispWebOpsServer::actingAs(mcpOperator())
        ->tool($tool, ['transaction' => 'REF-HIDDEN', 'amount' => 10])
        ->assertHasErrors();

    expect($transaction->fresh()->status)->toBe(TransactionStatus::pending);
})->with([ReconcileTransactionTool::class, RefundTransactionTool::class, CancelTransactionTool::class]);

it('protects the web route with authentication and a throttle by default', function (): void {
    config()->set('sisp.mcp.local', false);
    config()->set('sisp.mcp.web.enabled', true);
    config()->set('sisp.mcp.web.middleware', (require dirname(__DIR__, 3).'/config/sisp.php')['mcp']['web']['middleware']);
    require dirname(__DIR__, 3).'/routes/ai.php';

    expect(Mcp::getWebServer('sisp/mcp')?->gatherMiddleware())
        ->toContain('auth:sanctum')
        ->toContain('throttle:60,1');
});
