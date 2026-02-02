<?php

declare(strict_types=1);

namespace Akira\Sisp\Contracts;

use Akira\Sisp\ValueObjects\SispCredentials;

interface SispCredentialsResolver
{
    public function resolve(): SispCredentials;
}
