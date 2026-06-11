<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\Configuration\EnvSispCredentialsResolver;
use Akira\Sisp\ValueObjects\SispCredentials;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;

#[Bind(EnvSispCredentialsResolver::class)]
#[Singleton]
interface SispCredentialsResolver
{
    public function resolve(): SispCredentials;
}
