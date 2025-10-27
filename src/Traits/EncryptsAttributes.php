<?php

declare(strict_types=1);

namespace Akira\Sisp\Traits;

use Illuminate\Support\Facades\Crypt;

trait EncryptsAttributes
{
    protected function encryptable(): array
    {
        return [];
    }

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

        if ($this->shouldEncrypt($key) && $value !== null && is_string($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $value;
            }
        }

        return $value;
    }

    public function setAttribute($key, $value): static
    {
        if ($this->shouldEncrypt($key) && $value !== null && is_string($value) && !$this->isEncrypted($value)) {
            $value = Crypt::encryptString($value);
        }

        return parent::setAttribute($key, $value);
    }

    private function isEncrypted(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}