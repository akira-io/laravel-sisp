<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Akira\Sisp\Support\Countries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Look up the SISP numeric code, name, and flag for a country, or list every supported country.')]
final class CountryReferenceTool extends Tool
{
    public function handle(Request $request): Response
    {
        $alpha2 = $request->get('alpha2');

        if ($alpha2 === null) {
            return Response::json(['countries' => Countries::all()]);
        }

        $alpha2 = mb_strtolower(mb_trim((string) $alpha2));
        $name = Countries::getName($alpha2);

        if ($name === null) {
            return Response::error("Unknown country code \"{$alpha2}\". Use an ISO 3166-1 alpha-2 code such as \"cv\".");
        }

        return Response::json([
            'alpha2' => mb_strtoupper($alpha2),
            'name' => $name,
            'numeric' => Countries::getNumericCode($alpha2),
            'flag' => Countries::getFlag($alpha2),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'alpha2' => $schema->string()
                ->description('ISO 3166-1 alpha-2 country code, e.g. "cv". Omit to list all countries.')
                ->min(2)
                ->max(2),
        ];
    }
}
