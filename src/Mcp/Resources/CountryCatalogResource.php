<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Resources;

use Akira\Sisp\Support\Countries;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('sisp://countries')]
#[Description('Every country laravel-sisp supports with its ISO alpha-2, SISP numeric code, name, and flag URL.')]
final class CountryCatalogResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::json(['countries' => Countries::all()]);
    }
}
