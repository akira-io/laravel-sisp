<?php

declare(strict_types=1);

use Laravel\Mcp\Facades\Mcp;

function loadAiRoutes(): void
{
    require dirname(__DIR__, 3).'/routes/ai.php';
}

it('registers both local servers', function (): void {
    config()->set('sisp.mcp.local', true);
    config()->set('sisp.mcp.web.enabled', false);

    loadAiRoutes();

    expect(Mcp::getLocalServer('sisp-dev'))->not->toBeNull()
        ->and(Mcp::getLocalServer('sisp-ops'))->not->toBeNull();
});

it('does not register the web server when the web transport is disabled', function (): void {
    config()->set('sisp.mcp.local', false);
    config()->set('sisp.mcp.web.enabled', false);

    loadAiRoutes();

    expect(Mcp::getWebServer('sisp/mcp'))->toBeNull();
});

it('registers the web server when the web transport is enabled', function (): void {
    config()->set('sisp.mcp.local', false);
    config()->set('sisp.mcp.web.enabled', true);
    config()->set('sisp.mcp.web.path', '/sisp/mcp');

    loadAiRoutes();

    expect(Mcp::getWebServer('sisp/mcp'))->not->toBeNull();
});
