<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Servers;

use Akira\Sisp\Mcp\Prompts\DiagnosePaymentFailurePrompt;
use Akira\Sisp\Mcp\Prompts\IntegrateSispPrompt;
use Akira\Sisp\Mcp\Resources\CountryCatalogResource;
use Akira\Sisp\Mcp\Resources\DocsIndexResource;
use Akira\Sisp\Mcp\Resources\EnumCatalogResource;
use Akira\Sisp\Mcp\Resources\ErrorCodeCatalogResource;
use Akira\Sisp\Mcp\Tools\Dev\ConfigReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\CountryReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\DoctorTool;
use Akira\Sisp\Mcp\Tools\Dev\EnumReferenceTool;
use Akira\Sisp\Mcp\Tools\Dev\EnvScaffoldTool;
use Akira\Sisp\Mcp\Tools\Dev\ErrorCodeLookupTool;
use Akira\Sisp\Mcp\Tools\Dev\GetDocTool;
use Akira\Sisp\Mcp\Tools\Dev\SearchDocsTool;
use Akira\Sisp\Mcp\Tools\Dev\SimulateSandboxCallbackTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Override;

#[Name('sisp-dev')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    Developer assistant for the akira/laravel-sisp payment gateway package.

    Use this server to integrate laravel-sisp into a Laravel application: search
    the package documentation, look up configuration keys and required .env
    variables, resolve SISP error codes to recommended actions, inspect enums and
    supported countries, and simulate sandbox callback payloads for local testing.

    Every tool here is read-only. It never touches a live gateway, never writes to
    the database, and never moves money. For runtime payment operations use the
    sisp-ops server instead.
    MARKDOWN)]
final class SispDevServer extends Server
{
    #[Override]
    protected array $tools = [
        SearchDocsTool::class,
        GetDocTool::class,
        ConfigReferenceTool::class,
        EnvScaffoldTool::class,
        EnumReferenceTool::class,
        ErrorCodeLookupTool::class,
        CountryReferenceTool::class,
        SimulateSandboxCallbackTool::class,
        DoctorTool::class,
    ];

    #[Override]
    protected array $resources = [
        DocsIndexResource::class,
        EnumCatalogResource::class,
        CountryCatalogResource::class,
        ErrorCodeCatalogResource::class,
    ];

    #[Override]
    protected array $prompts = [
        IntegrateSispPrompt::class,
        DiagnosePaymentFailurePrompt::class,
    ];
}
