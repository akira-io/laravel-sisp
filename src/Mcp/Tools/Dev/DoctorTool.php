<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Akira\Sisp\Support\Diagnostics;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Run laravel-sisp diagnostics: invoice storage configuration, disk accessibility, and invoice/PDF counts.')]
final class DoctorTool extends Tool
{
    public function handle(Request $request, Diagnostics $diagnostics): Response
    {
        return Response::json($diagnostics->all());
    }
}
