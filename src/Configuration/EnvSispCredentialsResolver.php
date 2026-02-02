<?php

declare(strict_types=1);

namespace Akira\Sisp\Configuration;

use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\ValueObjects\SispCredentials;

final readonly class EnvSispCredentialsResolver implements SispCredentialsResolver
{
    public function __construct(
        private LoadConfig $config,
    ) {}

    public function resolve(): SispCredentials
    {
        return SispCredentials::fromConfig($this->config);
    }
}
