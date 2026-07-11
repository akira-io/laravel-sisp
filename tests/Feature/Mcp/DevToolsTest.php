<?php

declare(strict_types=1);

use Akira\Sisp\Mcp\Servers\SispDevServer;
use Akira\Sisp\Mcp\Tools\Dev\ConfigReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\CountryReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\DoctorTool;
use Akira\Sisp\Mcp\Tools\Dev\EnumReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\EnvScaffoldTool;
use Akira\Sisp\Mcp\Tools\Dev\ErrorCodeLookupTool;
use Akira\Sisp\Mcp\Tools\Dev\SearchDocsTool;
use Akira\Sisp\Mcp\Tools\Dev\SimulateSandboxCallbackTool;
use Akira\Sisp\Models\Transaction;

it('searches the documentation', function (): void {
    SispDevServer::tool(SearchDocsTool::class, ['query' => 'idempotency'])
        ->assertOk()
        ->assertSee('idempotency');
});

it('rejects an empty docs query', function (): void {
    SispDevServer::tool(SearchDocsTool::class, ['query' => '  '])
        ->assertHasErrors();
});

it('lists enum cases', function (): void {
    SispDevServer::tool(EnumReferenceTool::class, ['enum' => 'transaction_status'])
        ->assertOk()
        ->assertSee('completed')
        ->assertSee('refunded');
});

it('rejects an unknown enum', function (): void {
    SispDevServer::tool(EnumReferenceTool::class, ['enum' => 'nope'])
        ->assertHasErrors();
});

it('resolves a known error code', function (): void {
    SispDevServer::tool(ErrorCodeLookupTool::class, ['code' => '51'])
        ->assertOk()
        ->assertSee('funds');
});

it('errors on an unknown error code', function (): void {
    SispDevServer::tool(ErrorCodeLookupTool::class, ['code' => '4242'])
        ->assertHasErrors();
});

it('returns the numeric code for a country', function (): void {
    SispDevServer::tool(CountryReferenceTool::class, ['alpha2' => 'cv'])
        ->assertOk()
        ->assertSee('132');
});

it('describes a config key', function (): void {
    SispDevServer::tool(ConfigReferenceTool::class, ['key' => 'currency'])
        ->assertOk()
        ->assertSee('currency');
});

it('redacts the pos auth code in the config reference', function (): void {
    SispDevServer::tool(ConfigReferenceTool::class, ['key' => 'posAutCode'])
        ->assertOk()
        ->assertSee('redacted');
});

it('scaffolds env variables', function (): void {
    SispDevServer::tool(EnvScaffoldTool::class, ['mode' => 'production'])
        ->assertOk()
        ->assertSee('SISP_POS_ID');
});

it('runs diagnostics without throwing', function (): void {
    SispDevServer::tool(DoctorTool::class, [])
        ->assertOk()
        ->assertSee('invoices');
});

it('simulates a sandbox callback without persisting a transaction', function (): void {
    config()->set('sisp.sandbox', true);

    SispDevServer::tool(SimulateSandboxCallbackTool::class, ['amount' => 100, 'status' => 'success'])
        ->assertOk();

    expect(Transaction::query()->count())->toBe(0);
});
