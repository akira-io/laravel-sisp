<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\ValueObjects\SispCredentials;

final readonly class ScopedSispCredentialsResolver implements SispCredentialsResolver
{
    public function __construct(
        private SispCredentials $credentials,
    ) {}

    public function resolve(): SispCredentials
    {
        return $this->credentials;
    }
}
