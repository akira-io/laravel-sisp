<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Akira\Sisp\Mcp\Concerns\DescribesEnums;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List the cases of a laravel-sisp enum with their values and labels.')]
final class EnumReferenceTool extends Tool
{
    use DescribesEnums;

    public function handle(Request $request): Response
    {
        $name = (string) $request->get('enum');
        $enumClass = self::ENUMS[$name] ?? null;

        if ($enumClass === null) {
            return Response::error("Unknown enum \"{$name}\". Available: ".implode(', ', array_keys(self::ENUMS)));
        }

        return Response::json(['enum' => $name, 'cases' => $this->describeEnum($enumClass)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'enum' => $schema->string()
                ->description('Which enum to describe.')
                ->enum(array_keys(self::ENUMS))
                ->required(),
        ];
    }
}
