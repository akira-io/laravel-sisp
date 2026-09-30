<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final readonly class LegacyPayload
{
    private const int MAX_LAYERS = 4;

    /**
     * @return array<array-key, mixed>|null
     */
    public static function decode(mixed $value): ?array
    {
        for ($layer = 0; $layer <= self::MAX_LAYERS; $layer++) {
            if (is_array($value)) {
                return $value;
            }

            if (! is_string($value)) {
                return null;
            }

            $value = self::unwrap($value);
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>|null
     */
    public static function refunds(array $payload): ?array
    {
        $refunds = $payload['refunds'] ?? null;

        return $refunds === null ? [] : self::decode($refunds);
    }

    private static function unwrap(string $value): mixed
    {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_string($decoded))) {
            return $decoded;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }
}
