<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

final class SensitiveData
{
    private const array SENSITIVE_KEY_FRAGMENTS = [
        'authorization', 'cookie', 'password', 'passwd', 'secret', 'token',
        'card', 'cvv', 'cvc', 'key', 'pin', 'autcode',
    ];

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function redact(array $data): array
    {
        return collect($data)
            ->map(fn (mixed $value, string|int $key): mixed => self::redactValue((string) $key, $value))
            ->all();
    }

    public static function redactValue(string $key, mixed $value): mixed
    {
        if (self::isSensitiveKey($key)) {
            return '[redacted]';
        }

        return is_array($value) ? self::redact($value) : $value;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $key = mb_strtolower($key);

        return array_any(self::SENSITIVE_KEY_FRAGMENTS, fn (string $fragment): bool => str_contains($key, $fragment));
    }
}
