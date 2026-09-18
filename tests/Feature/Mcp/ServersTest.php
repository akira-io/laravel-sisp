<?php

declare(strict_types=1);

use Akira\Sisp\Mcp\Prompts\DiagnosePaymentFailurePrompt;
use Akira\Sisp\Mcp\Prompts\IntegrateSispPrompt;
use Akira\Sisp\Mcp\Resources\CountryCatalogResource;
use Akira\Sisp\Mcp\Resources\DocsIndexResource;
use Akira\Sisp\Mcp\Resources\EnumCatalogResource;
use Akira\Sisp\Mcp\Servers\SispDevServer;
use Akira\Sisp\Mcp\Servers\SispOpsServer;
use Akira\Sisp\Mcp\Servers\SispWebOpsServer;
use Akira\Sisp\Mcp\Tools\Ops\CancelTransactionTool;
use Akira\Sisp\Mcp\Tools\Ops\RefundTransactionTool;
use Laravel\Mcp\Server\Transport\FakeTransporter;

it('exposes the docs index resource', function (): void {
    SispDevServer::resource(DocsIndexResource::class)
        ->assertOk()
        ->assertSee('installation');
});

it('exposes the enum and country catalog resources', function (): void {
    SispDevServer::resource(EnumCatalogResource::class)->assertOk()->assertSee(['transaction_status', 'refunded']);
    SispDevServer::resource(CountryCatalogResource::class)->assertOk()->assertSee('132');
});

it('does not teach the deprecated message-type error mapping', function (): void {
    SispDevServer::resource(EnumCatalogResource::class)->assertOk()->assertDontSee(['error_message', 'insufficientFunds']);
});

it('renders the integration prompt for a stack', function (): void {
    SispDevServer::prompt(IntegrateSispPrompt::class, ['stack' => 'inertia-react'])
        ->assertOk()
        ->assertSee('inertia-react');
});

it('renders the failure diagnosis prompt around the stored refusal reason', function (): void {
    SispDevServer::prompt(DiagnosePaymentFailurePrompt::class, ['transaction' => 'R20260918120000ABCDEFGHIJ'])
        ->assertOk()
        ->assertSee(['R20260918120000ABCDEFGHIJ', 'get-transaction-tool', 'error_message'])
        ->assertDontSee('insufficient funds');
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

it('only names tools that the servers register in prompts and guidance', function (): void {
    $registered = collect([SispDevServer::class, SispOpsServer::class])
        ->flatMap(fn (string $server): array => new ReflectionProperty($server, 'tools')->getValue(new $server(new FakeTransporter)))
        ->map(fn (string $tool): string => resolve($tool)->name())
        ->all();

    $referenced = collect(glob(dirname(__DIR__, 3).'/src/Mcp/{Prompts,Tools/Dev}/*.php', GLOB_BRACE) ?: [])
        ->flatMap(fn (string $file): array => preg_match_all('/[a-z]+(?:-[a-z]+)*-tool\b/', (string) file_get_contents($file), $matches) ? $matches[0] : [])
        ->unique()
        ->values()
        ->all();

    expect($referenced)->not->toBeEmpty()
        ->and(array_diff($referenced, $registered))->toBe([]);
});

it('describes every primitive the servers register', function (string $server): void {
    $instance = new $server(new FakeTransporter);

    $primitives = collect(['tools', 'resources', 'prompts'])
        ->flatMap(fn (string $kind): array => new ReflectionProperty($instance, $kind)->getValue($instance))
        ->map(fn (string $class): array => resolve($class)->toArray());

    expect($primitives)->not->toBeEmpty()
        ->each(fn ($description) => $description->toHaveKeys(['name', 'description']));
})->with([SispDevServer::class, SispOpsServer::class, SispWebOpsServer::class]);
