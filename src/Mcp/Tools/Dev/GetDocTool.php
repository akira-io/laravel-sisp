<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Tools\Dev;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Return the full markdown content of a single laravel-sisp documentation page by its slug.')]
final class GetDocTool extends Tool
{
    public function handle(Request $request): Response
    {
        $slug = basename((string) $request->get('doc'), '.md');
        $file = dirname(__DIR__, 4)."/docs/{$slug}.md";

        if (! is_file($file)) {
            return Response::error("Unknown doc \"{$slug}\". Use one of: ".implode(', ', $this->slugs()));
        }

        return Response::text((string) file_get_contents($file));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'doc' => $schema->string()
                ->description('Documentation slug, e.g. "01-installation" or "14-idempotency".')
                ->enum($this->slugs())
                ->required(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function slugs(): array
    {
        return array_map(
            static fn (string $file): string => basename($file, '.md'),
            glob(dirname(__DIR__, 4).'/docs/*.md') ?: [],
        );
    }
}
