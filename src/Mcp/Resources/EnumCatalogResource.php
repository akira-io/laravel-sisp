<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Resources;

use Akira\Sisp\Mcp\Concerns\DescribesEnums;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('sisp://enums')]
#[Description('Catalog of every laravel-sisp enum with its cases, values, and labels.')]
final class EnumCatalogResource extends Resource
{
    use DescribesEnums;

    public function handle(Request $request): Response
    {
        return Response::json(array_map($this->describeEnum(...), self::ENUMS));
    }
}
