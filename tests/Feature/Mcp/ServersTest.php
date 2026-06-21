<?php

declare(strict_types=1);

use Akira\Sisp\Mcp\Prompts\DiagnosePaymentFailurePrompt;
use Akira\Sisp\Mcp\Prompts\IntegrateSispPrompt;
use Akira\Sisp\Mcp\Resources\CountryCatalogResource;
use Akira\Sisp\Mcp\Resources\DocsIndexResource;
use Akira\Sisp\Mcp\Resources\EnumCatalogResource;
use Akira\Sisp\Mcp\Resources\ErrorCodeCatalogResource;
use Akira\Sisp\Mcp\Servers\SispDevServer;
use Akira\Sisp\Mcp\Servers\SispWebOpsServer;
use Akira\Sisp\Mcp\Tools\Ops\CancelTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\RefundTransactionTool;
use Laravel\Mcp\Server\Transport\FakeTransporter;

it('exposes the docs index resource', function (): void {
    SispDevServer::resource(DocsIndexResource::class)
        ->assertOk()
        ->assertSee('installation');
});

it('exposes the enum, country, and error-code catalog resources', function (): void {
    SispDevServer::resource(EnumCatalogResource::class)->assertOk()->assertSee('transaction_status');
    SispDevServer::resource(CountryCatalogResource::class)->assertOk()->assertSee('132');
    SispDevServer::resource(ErrorCodeCatalogResource::class)->assertOk()->assertSee('insufficientFunds');
});

it('renders the integration prompt for a stack', function (): void {
    SispDevServer::prompt(IntegrateSispPrompt::class, ['stack' => 'inertia-react'])
        ->assertOk()
        ->assertSee('inertia-react');
});

it('renders the failure diagnosis prompt for a code', function (): void {
    SispDevServer::prompt(DiagnosePaymentFailurePrompt::class, ['code' => '51'])
        ->assertOk()
        ->assertSee('funds');
});

it('hides destructive tools on the web server by default', function (): void {
    config()->set('sisp.mcp.web.expose_destructive', false);

    expect(webOpsTools())
        ->not->toContain(RefundTransactionTool::class)
        ->not->toContain(CancelTransactionTool::class);
});

it('exposes destructive tools on the web server when opted in', function (): void {
    config()->set('sisp.mcp.web.expose_destructive', true);

    expect(webOpsTools())
        ->toContain(RefundTransactionTool::class)
        ->toContain(CancelTransactionTool::class);
});

function webOpsTools(): array
{
    $server = new SispWebOpsServer(new FakeTransporter);

    $property = new ReflectionProperty($server, 'tools');

    return $property->getValue($server);
}
