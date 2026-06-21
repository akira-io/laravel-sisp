<?php

declare(strict_types=1);

use Akira\Sisp\Mcp\Servers\SispDevServer;
use Akira\Sisp\Mcp\Servers\SispOpsServer;
use Akira\Sisp\Mcp\Servers\SispWebOpsServer;
use Laravel\Mcp\Facades\Mcp;

if (config('sisp.mcp.local', true)) {
    Mcp::local('sisp-dev', SispDevServer::class);
    Mcp::local('sisp-ops', SispOpsServer::class);
}

if (config('sisp.mcp.web.enabled', false)) {
    Mcp::web(config('sisp.mcp.web.path', '/sisp/mcp'), SispWebOpsServer::class)
        ->middleware(config('sisp.mcp.web.middleware', ['auth:sanctum']));
}
