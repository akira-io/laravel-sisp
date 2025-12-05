<?php

declare(strict_types=1);

namespace Akira\Sisp\Traits;

use Illuminate\Support\Facades\Crypt;
use Throwable;

trait EncryptsAttributes
{
    public function shouldEncrypt(string $key): bool
    {
        $encryptable = $this->encryptable();

        if (empty($encryptable)) {
            return true;
        }

        return in_array($key, $encryptable);
    }

    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if ($this->shouldEncrypt($key)) {
            // Prefer decrypting from raw attribute to bypass casts when necessary
            $raw = $this->attributes[$key] ?? null;

            if (is_string($raw)) {
                try {
                    $decrypted = Crypt::decryptString($raw);
                    $decoded = json_decode($decrypted, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }

                    return $decrypted;
                } catch (Throwable) {
                    // Fall through and return the original casted value
                }
            }

            if (is_string($value)) {
                try {
                    return Crypt::decryptString($value);
                } catch (Throwable) {
                    return $value;
                }
            }
        }

        return $value;
    }

    public function setAttribute($key, $value): static
    {
        if ($this->shouldEncrypt($key) && $value !== null) {
            if (! is_string($value)) {
                $value = json_encode($value);
            }

            if (is_string($value) && ! $this->isEncrypted($value)) {
                $value = Crypt::encryptString($value);
            }
        }

        return parent::setAttribute($key, $value);
    }

    protected function encryptable(): array
    {
        return [];
    }

    private function isEncrypted(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
