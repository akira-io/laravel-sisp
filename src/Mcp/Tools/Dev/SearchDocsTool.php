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
#[Description('Search the laravel-sisp documentation for a keyword and return matching sections with their source file.')]
final class SearchDocsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $query = mb_strtolower(mb_trim((string) $request->get('query')));

        if ($query === '') {
            return Response::error('Provide a non-empty "query" to search the documentation.');
        }

        $limit = max(1, min(20, (int) ($request->get('limit') ?? 10)));

        $matches = [];

        foreach (glob(dirname(__DIR__, 4).'/docs/*.md') ?: [] as $file) {
            $contents = (string) file_get_contents($file);

            foreach (preg_split('/\n(?=#{1,6}\s)/', $contents) ?: [] as $section) {
                if (str_contains(mb_strtolower($section), $query)) {
                    $heading = mb_trim((string) (preg_split('/\n/', $section)[0] ?? ''));

                    $matches[] = [
                        'doc' => basename($file, '.md'),
                        'heading' => $heading,
                        'excerpt' => mb_substr(mb_trim($section), 0, 600),
                    ];

                    if (count($matches) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        if ($matches === []) {
            return Response::text("No documentation sections matched \"{$query}\".");
        }

        return Response::json(['query' => $query, 'matches' => $matches]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Keyword or phrase to search for across the docs/ folder.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of matching sections to return (1-20).')
                ->default(10),
        ];
    }
}
