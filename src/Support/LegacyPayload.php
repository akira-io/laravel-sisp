<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use LogicException;

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
     * Decodes a stored payload before it is rewritten or trusted, refusing a value it cannot
     * read rather than treating it as empty and overwriting or ignoring what it holds.
     *
     * @return array<array-key, mixed>
     */
    public static function decodeStored(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        $payload = self::decode($value);

        throw_if($payload === null, LogicException::class, 'The stored transaction payload could not be decoded.');

        return $payload;
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

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    public static function refundHistory(array $payload): array
    {
        $refunds = self::refunds($payload);

        throw_if($refunds === null, LogicException::class, 'The stored refund history could not be decoded.');

        return $refunds;
    }

    /**
     * Whether a value that could not be decoded may still hold refund history: it names
     * refunds in the clear, or it is an encryption envelope whose contents cannot be seen.
     */
    public static function mayHoldRefunds(string $value): bool
    {
        return str_contains($value, 'refunds') || self::isEncryptionEnvelope($value);
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

    private static function isEncryptionEnvelope(string $value): bool
    {
        $json = base64_decode($value, true);

        if ($json === false) {
            return false;
        }

        $envelope = json_decode($json, true);

        return is_array($envelope) && array_key_exists('iv', $envelope) && array_key_exists('value', $envelope);
    }
}
