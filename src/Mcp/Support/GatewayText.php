<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Support;

use Illuminate\Support\Str;

final class GatewayText
{
    private const int MAX_LENGTH = 255;

    /**
     * @param  array<string, ?string>  $fields
     * @return array<string, true|string|null>
     */
    public static function wrap(array $fields): array
    {
        return [
            'untrusted' => true,
            ...array_map(self::clean(...), $fields),
        ];
    }

    public static function clean(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return Str::of($text)
            ->replaceMatches('/\p{C}+/u', ' ')
            ->replaceMatches('/[`<>\[\]{}#*_|\\\\]+/', ' ')
            ->squish()
            ->limit(self::MAX_LENGTH, '')
            ->toString();
    }
}
