<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('sisp://docs')]
#[Description('Index of the laravel-sisp documentation pages with their titles and slugs.')]
final class DocsIndexResource extends Resource
{
    public function handle(Request $request): Response
    {
        $index = [];

        foreach (glob(dirname(__DIR__, 3).'/docs/*.md') ?: [] as $file) {
            $first = (string) (preg_split('/\n/', (string) file_get_contents($file))[0] ?? '');

            $index[] = [
                'slug' => basename($file, '.md'),
                'title' => mb_trim(mb_ltrim($first, '# ')),
            ];
        }

        return Response::json(['docs' => $index]);
    }
}
